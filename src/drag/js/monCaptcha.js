(function () {
    /**
     * 拖动验证码
     * 
     * @author Mon <985558837@qq.com>
     * @version 1.0.0
     */
    class monCaptcha {
        // 版本
        version = '1.0.0'
        // 渲染模式，支持 insert, modal 两种方式
        mode = 'modal'
        // 触发元素，insert模式时作为插入的父级元素，modal模式时作为触发点击事件元素
        element = ''
        // 图片URL
        url = ''
        // 图片宽度
        img_width = 300
        // 图片高度
        img_height = 160
        // 图片标志位宽度
        img_mark_width = 50
        // 图片标志位高度
        img_mark_height = 50
        // 图片地址添加随机因子
        randon_url = true
        // 显示时调用方法
        onShow = null
        // 关闭时调用方法
        onClose = null

        // 根元素ID
        _root = ''
        // js图片句柄
        _img = null
        // 渲染验证码背景图片中
        _draw_bg = false
        // 图片加载中
        _img_loaded = false
        // 图像标志为偏移
        _img_mark_offset = 0
        // 开始拖动
        _doing = false
        // 拖动标志位起始x坐标
        _mark_start_x = 0
        // 拖动标志位起始y坐标
        _mark_start_y = 0
        // 标志位宽度
        _mark_width = 32
        // 标志位高度
        _mark_height = 32
        // 背景canvas
        _canvas_bg_dom = null
        // 浮块canvas
        _canvas_mark_dom = null
        // 拖块dom
        _drag_mark_dom = null
        // 拖条提示
        _drag_line_dom = null
        // 图片加载
        _verify_loading_dom = null
        // 验证加载
        _verify_check_loading_dom = null
        // tips
        _verify_tips_dom = null
        // 拖动开始时间
        _start_time = 0

        // 构造方法
        constructor(option) {
            // 定义配置信息
            option = option || {}
            if (!option.mode || !['insert', 'modal'].includes(option.mode)) {
                console.error('请配置验证模式[mode]，支持insert、modal两种模式');
                return;
            }
            if (!option.element) {
                console.error('请配置验证触发元素[element]');
                return;
            }
            if (!option.url) {
                console.error('请配置验证图片获取URL[url]');
                return;
            }
            if (!option.onCheck || typeof option.onCheck !== 'function') {
                console.error('请配置验证回调方法[onCheck]');
                return;
            }
            this.mode = option.mode
            this.element = option.element
            this.url = option.url
            this.onCheck = option.onCheck
            this.onShow = option.onShow
            this.onClose = option.onClose
            this.img_width = option.img_width || 300
            this.img_height = option.img_height || 160
            this.img_mark_width = option.img_mark_width || 50
            this.img_mark_height = option.img_mark_height || 50
            this.randon_url = (option.randon_url == undefined) ? true : option.randon_url

            // 渲染样式
            this._build_style();
            // 渲染html
            this._build_html();

            // 获取dom
            this._canvas_bg_dom = document.querySelector(this._root + ' .mon-verify-bg')
            this._canvas_mark_dom = document.querySelector(this._root + ' .mon-verify-mark')
            this._drag_mark_dom = document.querySelector(this._root + ' .mon-verify-drag-mark')
            this._drag_line_dom = document.querySelector(this._root + ' .mon-verify-drag-line span')
            this._verify_loading_dom = document.querySelector(this._root + ' .mon-verify-loading')
            this._verify_check_loading_dom = document.querySelector(this._root + ' .mon-verify-check-loading')
            this._verify_tips_dom = document.querySelector(this._root + ' .mon-verify-tips')

            // 初始化绑定事件
            this._init();
            // 插入模式，触发渲染
            if (this.mode == 'insert') {
                this.show()
            }
        }

        // 初始化
        _init() {
            // 设置canvas元素宽高
            this._canvas_bg_dom.width = this.img_width
            this._canvas_bg_dom.height = this.img_height
            this._canvas_mark_dom.width = this.img_width
            this._canvas_mark_dom.height = this.img_height
            // 绑定拖动
            this._bind(this._drag_mark_dom, 'mousedown', this._drag_start)
            this._bind(document, 'mousemove', this._drag_move)
            this._bind(document, 'mouseup', this._drag_end)
            this._bind(this._drag_mark_dom, 'touchstart', this._drag_start)
            this._bind(document, 'touchmove', this._drag_move)
            this._bind(document, 'touchend', this._drag_end)
            // 刷新
            this._bind(document.querySelector(this._root + ' .mon-verify-refresh'), 'click', this.refresh)
            // 弹窗模式，绑定打开关闭事件
            if (this.mode == 'modal') {
                this._bind(document.querySelector(this.element), 'click', this.show)
                this._bind(document.querySelector(this.element), 'touchstart', this.show)
                this._bind(document.querySelector(this._root + ' .mon-verify-close'), 'click', this.close)
                this._bind(document.querySelector(this._root + ' .mon-verify-close'), 'touchstart', this.close)
            }
        }
        // 绑定事件
        _bind(elm, eventType, fn) {
            let that = this
            elm.addEventListener(eventType, function (event) {
                let el = this
                fn.call(that, event, el)
            })
        }
        // 开始拖动
        _drag_start(e) {
            if (!this._img_loaded) {
                return false;
            }
            if (this._doing) {
                return true;
            }
            e.preventDefault();
            let theEvent = window.event || e;
            if (theEvent.touches) {
                theEvent = theEvent.touches[0];
            }
            this._mark_start_x = theEvent.clientX;
            this._mark_start_y = theEvent.clientY;
            // 标志拖动
            this._doing = true;
            this._render_bg()
            this._render_mark()
            this._drag_line_dom.style.display = 'none'
            this._start_time = (new Date).getTime()
        }
        // 拖动中
        _drag_move(e) {
            if (!this._doing) {
                return true;
            }
            e.preventDefault();
            let theEvent = window.event || e;
            if (theEvent.touches) {
                theEvent = theEvent.touches[0];
            }
            let offset = theEvent.clientX - this._mark_start_x;
            if (offset < 0) {
                offset = 0;
            }
            let max_off = this.img_width - this._mark_width;
            if (offset > max_off) {
                offset = max_off;
            }

            this._drag_mark_dom.style.cssText = "transform: translate(" + offset + "px, 0px)";
            this._img_mark_offset = offset / max_off * (this.img_width - this.img_mark_width);
            // 同步图片移动
            this._render_bg()
            this._render_mark()
            this._drag_line_dom.style.display = 'none'
        }
        // 拖动结束
        _drag_end(e) {
            if (!this._doing) {
                return true;
            }
            e.preventDefault();
            let theEvent = window.event || e;
            if (theEvent.touches) {
                theEvent = theEvent.touches[0];
            }
            this._doing = false

            let now = (new Date).getTime();
            let drag_time = now - this._start_time

            // 打开透明幕布，防止重复操作
            this._verify_check_loading_dom.style.display = 'block'
            let result = this.onCheck(this._img_mark_offset, drag_time, this)
            if (result !== false) {
                // 返回false，则不处理透明幕布，其他情况关闭幕布
                this._verify_check_loading_dom.style.display = 'none'
            }
        }

        // 渲染封面图片
        _render_thumb() {
            let canvas = this._canvas_bg_dom.getContext('2d', { willReadFrequently: true });
            canvas.drawImage(this._img, 0, this.img_height * 2, this.img_width, this.img_height, 0, 0, this.img_width, this.img_height);
        }
        // 渲染背景图片
        _render_bg() {
            if (this._draw_bg) {
                // 防止拖动中不断渲染背景
                return;
            }
            this._draw_bg = true
            let canvas = this._canvas_bg_dom.getContext('2d', { willReadFrequently: true });
            canvas.clearRect(0, 0, this._canvas_bg_dom.width, this._canvas_bg_dom.height);
            canvas.drawImage(this._img, 0, 0, this.img_width, this.img_height, 0, 0, this.img_width, this.img_height);
        }
        // 渲染图片标志
        _render_mark() {
            let canvas = this._canvas_mark_dom.getContext('2d', { willReadFrequently: true });
            canvas.clearRect(0, 0, this._canvas_mark_dom.width, this._canvas_mark_dom.height);
            canvas.drawImage(this._img, 0, this.img_height, this.img_mark_width, this.img_height, this._img_mark_offset, 0, this.img_mark_width, this.img_height);
            let imageData = canvas.getImageData(0, 0, this.img_width, this.img_height);
            // 获取画布的像素信息，处理黑边
            let data = imageData.data;
            let x = this.img_height, y = this.img_width;
            for (let j = 0; j < x; j++) {
                let ii = 1, k1 = -1;
                for (let k = 0; k < y && k >= 0 && k > k1;) {
                    // 得到 RGBA 通道的值
                    let i = (j * y + k) * 4;
                    k += ii;
                    let r = data[i], g = data[i + 1], b = data[i + 2];
                    if (r + g + b < 200) {
                        data[i + 3] = 0;
                    } else {
                        let arr_pix = [1, -5];
                        let arr_op = [250, 0];
                        for (let i = 1; i < arr_pix[0] - arr_pix[1]; i++) {
                            let iiii = arr_pix[0] - 1 * i;
                            let op = parseInt(arr_op[0] - (arr_op[0] - arr_op[1]) / (arr_pix[0] - arr_pix[1]) * i);
                            let iii = (j * y + k + iiii * ii) * 4;
                            data[iii + 3] = op;
                        }
                        if (ii == -1) {
                            break;
                        }
                        k1 = k;
                        k = y - 1;
                        ii = -1;
                    };
                }
            }
            canvas.putImageData(imageData, 0, 0);
        }

        // 生成html
        _build_html() {
            let root = 'mon-verify-id-' + Math.floor(Math.random() * 1000000000)
            this._root = '#' + root
            let close_btn = this.mode == 'modal' ? `<span class="mon-verify-close" title="关闭"><svg t="1635921106546" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="4845" width="22" height="22"><path d="M872.802928 755.99406 872.864326 755.99406 872.864326 755.624646Z" p-id="4846" fill="#333"></path><path d="M927.846568 511.997953c0-229.315756-186.567139-415.839917-415.838893-415.839917-229.329059 0-415.85322 186.524161-415.85322 415.839917 0 229.300406 186.524161 415.84094 415.85322 415.84094C741.278405 927.838893 927.846568 741.29836 927.846568 511.997953M512.007675 868.171955c-196.375529 0-356.172979-159.827125-356.172979-356.174002 0-196.374506 159.797449-356.157629 356.172979-356.157629 196.34483 0 356.144326 159.783123 356.144326 356.157629C868.152001 708.34483 708.352505 868.171955 512.007675 868.171955" p-id="4847" fill="#333"></path><path d="M682.378947 642.227993 553.797453 513.264806 682.261267 386.229528c11.661597-11.514241 11.749602-30.332842 0.234337-41.995463-11.514241-11.676947-30.362518-11.765975-42.026162-0.222057L511.888971 471.195665 385.223107 344.130711c-11.602246-11.603269-30.393217-11.661597-42.025139-0.059352-11.603269 11.618619-11.603269 30.407544-0.059352 42.011836l126.518508 126.887922L342.137823 639.104863c-11.662621 11.543917-11.780301 30.305213-0.23536 41.96988 5.830799 5.89015 13.429871 8.833179 21.086248 8.833179 7.53972 0 15.136745-2.8847 20.910239-8.569166l127.695311-126.311801L640.293433 684.195827c5.802146 5.8001 13.428847 8.717546 21.056572 8.717546 7.599072 0 15.165398-2.917446 20.968567-8.659217C693.922864 672.681586 693.950494 653.889591 682.378947 642.227993" p-id="4848" fill="#333"></path></svg></span>` : ''
            let html = `<div class="mon-verify" id="${root}">
                        <div class="mon-verify-main" style="width: ${this.img_width}px; height: ${this.img_height}px;">
                            <div class="mon-verify-img">
                                <div class="mon-verify-loading"></div>
                                <canvas class="mon-verify-bg"></canvas>
                                <canvas class="mon-verify-mark"></canvas>
                            </div>
                            <div class="mon-verify-tool">
                                <span class="mon-verify-refresh" title="刷新">
                                    <svg t="1635920732640" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="3991" width="22" height="22"><path fill="#333" d="M774.630513 239.560074c-66.918086-71.697949-162.516374-114.716718-267.673365-114.716718-210.315006 0-382.391107 172.0761-382.391107 382.391107s172.0761 382.391107 382.391107 382.391107c176.855964 0 325.031724-124.277468 368.051517-286.792818l-100.377128 0c-38.238906 109.936855-143.396921 191.195553-267.673365 191.195553-157.736511 0-286.792818-129.057331-286.792818-286.792818 0-157.736511 129.057331-286.792818 286.792818-286.792818 81.257675 0 148.176784 33.459043 200.75528 86.037539l-152.956647 152.956647 334.591451 0 0-334.591451L774.630513 239.560074z" p-id="3992"></path></svg>
                                </span>
                                ${close_btn}
                            </div>
                            <div class="mon-verify-tips"></div>
                        </div>
                        <div class="mon-verify-oper">
                            <div class="mon-verify-drag">
                                <div class="mon-verify-drag-mark">
                                    <svg t="1635923624783" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="5738" width="16" height="16"><path d="M729.6 448H128v85.333333h601.6L597.333333 665.6l59.733334 59.733333 234.666666-234.666666L661.333333 256l-59.733333 59.733333 128 132.266667z" fill="#2c2c2c" p-id="5739"></path></svg>
                                </div>
                                <div class="mon-verify-drag-line"><span>向右拖动滑块填充完成拼图</span></div>
                            </div>
                        </div>
                        <div class="mon-verify-check-loading"></div>
                    </div>`

            if (this.mode == 'insert') {
                document.querySelector(this.element).insertAdjacentHTML('beforeend', html)
            } else {
                html = `<div class="mon-verify-modal"><div class="mon-verify-modal-main">${html}</div></div>`
                document.querySelector('body').insertAdjacentHTML('beforeend', html)
            }
        }
        // 生成style样式
        _build_style() {
            const css = `.mon-verify-modal{position:fixed;top:0;right:0;bottom:0;left:0;z-index:1000;display:none;width:100%;height:100%;background:rgba(0,0,0,.6);align-items:center;justify-content:center;}
        .mon-verify-modal-main{padding:6px;border-radius:3px;background:#fff;}
        .mon-verify{position:relative;overflow:hidden;box-sizing:border-box;margin:0 auto;background:#fff;}
        .mon-verify-main{position:relative;overflow:hidden;}
        .mon-verify-main .mon-verify-img{display:flex;width:100%;height:100%;align-items:center;justify-content:center;}
        .mon-verify-main .mon-verify-img .mon-verify-loading{margin:0 auto;width:32px;height:32px;border:6px solid #f3f3f3;border-top:6px solid #3498db;border-bottom:6px solid #3498db;border-radius:50%;animation:mon-verify-loading-spin 2s linear infinite;-webkit-animation:mon-verify-loading-spin 2s linear infinite;}
        .mon-verify-main .mon-verify-img .mon-verify-bg{position:absolute;top:0;right:0;bottom:0;left:0;width:100%;height:100%;}
        .mon-verify-main .mon-verify-img .mon-verify-mark{position:absolute;top:0;right:0;bottom:0;left:0;z-index:2;width:100%;height:100%;}
        .mon-verify-main .mon-verify-tool{position:absolute;top:8px;right:4px;z-index:3;-webkit-touch-callout:none;-webkit-user-select:none;-khtml-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;}
        .mon-verify-main .mon-verify-tool span{display:inline-block;margin:0 2px;cursor:pointer;}
        .mon-verify-main .mon-verify-tips{position:absolute;bottom:0;z-index:4;display:none;padding:6px 8px;width:100%;font-size:14px;line-height:16px;}
        .mon-verify-main .mon-verify-tips.mon-verify-success{background:#0c9;color:#fff;}
        .mon-verify-main .mon-verify-tips.mon-verify-error{background:red;color:#fff;}
        .mon-verify-oper{margin-top:10px;}
        .mon-verify-oper .mon-verify-drag{position:relative;font-size:14px;}
        .mon-verify-oper .mon-verify-drag-line{width:100%;height:32px;border:1px solid #e4e7eb;background:#f3f3f3;color:#000;text-align:center;line-height:32px;-webkit-touch-callout:none;-webkit-user-select:none;-khtml-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;}
        .mon-verify-oper .mon-verify-drag-mark{position:absolute;top:0;left:0;display:flex;width:32px;height:32px;border:1px solid #ccc;border-radius:4px;background:#fff;line-height:32px;cursor:pointer;align-items:center;justify-content:center;}
        .mon-verify-oper .mon-verify-drag-mark:active,.mon-verify-oper .mon-verify-drag-mark:focus,.mon-verify-oper .mon-verify-drag-mark:hover{background:#0cf;}
        .mon-verify-oper .mon-verify-drag-mark:active svg path,.mon-verify-oper .mon-verify-drag-mark:focus svg path,.mon-verify-oper .mon-verify-drag-mark:hover svg path{fill:#fff;}
        .mon-verify-check-loading{position:absolute;top:0;right:0;bottom:0;left:0;z-index:999;display:none;width:100%;height:100%;background:#fff;opacity:0;}
        @-webkit-keyframes mon-verify-loading-spin{0%{-webkit-transform:rotate(0);}
        100%{-webkit-transform:rotate(360deg);}
        }
        @keyframes mon-verify-loading-spin{0%{transform:rotate(0);}
        100%{transform:rotate(360deg);}
        }`
            const id = 'mon-verify'
            if (document.getElementById(id)) {
                return;
            }
            const head = document.getElementsByTagName('head')[0];
            const style = document.createElement('style');
            style.id = id
            style.type = 'text/css';
            if (style.styleSheet) {
                style.styleSheet.cssText = css;
            } else {
                style.appendChild(document.createTextNode(css));
            }
            head.appendChild(style);
        }

        // 生成二维码请求地址
        _buildURL(url) {
            return url + (url.match(/(\?|&)+/) ? "&v=" : "?v=") + Math.random()
        }

        // 显示
        show() {
            if (this.onShow && this.onShow(this) !== true) {
                return false;
            }

            this.refresh()
            // 弹窗模式，打开弹窗
            if (this.mode == 'modal') {
                let modal = document.querySelector(this._root).parentElement.parentElement
                modal.style.display = 'flex'
            }
        }
        // 刷新验证码
        refresh() {
            let that = this
            this._draw_bg = false;
            this._img_loaded = false;
            this._img_mark_offset = 0;
            this.hideTips()
            this._canvas_bg_dom.style.display = 'none'
            this._canvas_mark_dom.style.display = 'none'
            this._verify_check_loading_dom.style.display = 'none'
            this._verify_loading_dom.style.display = 'block'

            // 图片地址
            let img_url = this.url;
            if (this.randon_url) {
                img_url = this._buildURL(this.url)
            }
            // 加载图片
            this._img = new Image()
            this._img.src = img_url
            this._img.onload = function () {
                that._render_thumb()
                let ctx_mark = that._canvas_mark_dom.getContext('2d', { willReadFrequently: true });
                // 清理画布
                ctx_mark.clearRect(0, 0, that._canvas_mark_dom.width, that._canvas_mark_dom.height);

                that._img_loaded = true;
                that._canvas_bg_dom.style.display = 'block'
                that._verify_loading_dom.style.display = 'none'
                that._canvas_mark_dom.style.display = 'block'
            }

            this._drag_mark_dom.style.cssText = "transform: translate(0px, 0px)";
            this._drag_line_dom.style.display = 'block'
        }
        // 关闭
        close() {
            // 非弹窗模式，不支持
            if (this.mode != 'modal') {
                return false
            }
            if (this.onClose && this.onClose(this) !== true) {
                return false;
            }
            document.querySelector(this._root).parentElement.parentElement.style.display = 'none'
        }
        // 显示提示
        showTips(msg, success = true, callback = null, timeout = 2000) {
            let className = success ? 'mon-verify-success' : 'mon-verify-error'
            this._verify_tips_dom.style.display = 'block';
            this._verify_tips_dom.classList.remove('mon-verify-success', 'mon-verify-error')
            this._verify_tips_dom.classList.add(className)
            this._verify_tips_dom.innerHTML = `<span>${msg}</span>`
            setTimeout(() => {
                if (callback) {
                    callback.call(null, this)
                }
            }, timeout);
        }
        // 隐藏提示
        hideTips() {
            this._verify_tips_dom.style.display = 'none'
        }
    }

    // 支持cmd及amd，方便后续的可能需要的工程化
    if (typeof module !== "undefined" && module.exports) {
        module.exports = monCaptcha;
    } else if (typeof define === "function" && define.amd) {
        define(function () { return monCaptcha; });
    } else if (typeof layui != 'undefined' && layui.define) {
        layui.define(function (exports) {
            exports('monCaptcha', monCaptcha);
        })
    } else {
        !('monCaptcha' in window) && (window.monCaptcha = monCaptcha);
    }
})()
