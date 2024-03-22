<?php

declare(strict_types=1);

namespace mon\captcha\drive;

use mon\captcha\CaptchaInfo;
use mon\captcha\CaptchaDrive;

/**
 * 图像验证码驱动
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Image implements CaptchaDrive
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 数字验证码字符串
        'numSet'    => '23456789',
        // 验证码字符集合
        'enSet'   => 'abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',
        // 验证码图片高度
        'imageH'    => 0,
        // 验证码图片宽度
        'imageW'    => 0,
        // 背景颜色 rgb值
        'bg'        => [243, 251, 254],
        // 使用的字体
        'font'      => '',
        // 验证码字 体大小(px)
        'fontSize'  => 25,
        // 是否画混淆曲线
        'useCurve'  => false,
        // 是否添加杂点
        'useNoise'  => false
    ];

    /**
     * 验证码图片实例
     *
     * @var mixed
     */
    protected $_img;

    /**
     * 验证码字体颜色
     *
     * @var integer
     */
    protected $_color;

    /**
     * 构造方法
     *
     * @param array $config 配置信息
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 使用 $this->name 获取配置
     *
     * @param  string $name 配置名称
     * @return mixed    配置值
     */
    public function __get(string $name)
    {
        return $this->config[$name];
    }

    /**
     * 设置验证码配置
     *
     * @param  string $name  配置名称
     * @param  string $value 配置值
     * @return void
     */
    public function __set(string $name, $value)
    {
        return $this->config[$name] = $value;
    }

    /**
     * 设置配置信息
     *
     * @param array $config
     * @return Image
     */
    public function setConfig(array $config): Image
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * 获取配置信息
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 创建图片验证码
     *
     * @param array $typeArr  验证码类型，默认英文数字
     * @param integer $length  验证码长度，非运算型验证码有效
     * @return CaptchaInfo
     */
    public function create(array $typeArr = ['scalar'], int $length = 4): CaptchaInfo
    {
        // 随机获取验证码类型
        $typeKey = array_rand($typeArr, 1);
        $type = $typeArr[$typeKey];
        // 图片宽(px)
        if (!$this->imageW) {
            $this->imageW = $length * $this->fontSize * 1.5 + $length * $this->fontSize / 2;
        }
        // 图片高(px)
        if (!$this->imageH) {
            $this->imageH = $this->fontSize * 2.5;
        }
        // 建立一幅 $this->imageW x $this->imageH 的图像
        $this->_img = imagecreate(intval($this->imageW), intval($this->imageH));
        // 设置背景
        imagecolorallocate($this->_img, $this->bg[0], $this->bg[1], $this->bg[2]);
        // 验证码字体随机颜色
        $this->_color = imagecolorallocate($this->_img, mt_rand(1, 150), mt_rand(1, 150), mt_rand(1, 150));
        // 设置字体
        if (empty($this->font)) {
            $this->font = $this->getFont();
        }
        // 绘制杂点
        if ($this->useNoise) {
            $this->writeNoise();
        }
        // 绘制干扰线
        if ($this->useCurve) {
            $this->writeCurve();
        }

        // 验证码
        $code = [];
        // 验证码第N个字符的左边距
        $codeNX = 0;
        // 绘制验证码
        switch ($type) {
            case 'en':
                // 英文验证码
                for ($i = 0; $i < $length; $i++) {
                    $code[$i] = $this->enSet[mt_rand(0, strlen($this->enSet) - 1)];
                    $codeNX += mt_rand(intval($this->fontSize * 1.2), intval($this->fontSize * 1.6));
                    imagettftext($this->_img, $this->fontSize, mt_rand(-40, 40), $codeNX, intval($this->fontSize * 1.6), $this->_color, $this->font, $code[$i]);
                }
                break;
            case 'num':
                // 数字验证码
                for ($i = 0; $i < $length; $i++) {
                    $code[$i] = $this->numSet[mt_rand(0, strlen($this->numSet) - 1)];
                    $codeNX += mt_rand(intval($this->fontSize * 1.2), intval($this->fontSize * 1.6));
                    imagettftext($this->_img, $this->fontSize, mt_rand(-40, 40), $codeNX, intval($this->fontSize * 1.6), $this->_color, $this->font, $code[$i]);
                }
                break;
            case 'calcul':
                // 数字运算验证码
                $num1 = $this->numSet[mt_rand(0, strlen($this->numSet) - 1)] * $this->numSet[mt_rand(0, strlen($this->numSet) - 1)];
                $num2 = $this->numSet[mt_rand(0, strlen($this->numSet) - 1)] * $this->numSet[mt_rand(0, strlen($this->numSet) - 1)];
                if ($num1 > $num2) {
                    $code = [$num1 - $num2];
                    $expression = $num1 . '-' . $num2 . '=';
                } else {
                    $code = [$num1 + $num2];
                    $expression = $num1 . '+' . $num2 . '=';
                }
                imagettftext($this->_img, $this->fontSize * 1.2, 0, $this->fontSize, $this->fontSize + mt_rand(15, 30), $this->_color, $this->font, $expression);
                break;
            case 'scalar':
            default:
                // 默认混合数字英文验证码
                $codeSet = $this->numSet . $this->enSet;
                for ($i = 0; $i < $length; $i++) {
                    $code[$i] = $codeSet[mt_rand(0, strlen($codeSet) - 1)];
                    $codeNX += mt_rand(intval($this->fontSize * 1.2), intval($this->fontSize * 1.6));
                    imagettftext($this->_img, $this->fontSize, mt_rand(-40, 40), $codeNX, intval($this->fontSize * 1.6), $this->_color, $this->font, $code[$i]);
                }
                break;
        }

        // 获取输出图像
        ob_start();
        imagepng($this->_img);
        $img = ob_get_clean();
        imagedestroy($this->_img);
        // 验证码
        $captcha = implode('', $code);

        return new CaptchaInfo($img, $captcha);
    }

    /**
     * 校验验证码
     *
     * @param string $code   用户输入验证码
     * @param string $checkCode  创建的验证码
     * @param boolean $case  是否区分大小写
     * @return boolean
     */
    public function check(string $code, string $checkCode, bool $case = false): bool
    {
        $code = $case ? $code : strtolower($code);
        $checkCode = $case ? $checkCode : strtolower($checkCode);

        return $code === $checkCode;
    }

    /**
     * 获取相关脚本
     *
     * @return string
     */
    public function getScript(): string
    {
        return '';
    }

    /**
     * 获取字体库路径
     *
     * @return string
     */
    protected function getFont(): string
    {
        return dirname(__DIR__) . '/ttf/en.ttf';
    }

    /**
     * 绘制验证码杂点, 往图片上写不同颜色的字母或数字
     *
     * @return void
     */
    protected function writeNoise()
    {
        $codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
        for ($i = 0; $i < 10; $i++) {
            //杂点颜色
            $noiseColor = imagecolorallocate($this->_img, mt_rand(150, 225), mt_rand(150, 225), mt_rand(150, 225));
            for ($j = 0; $j < 5; $j++) {
                // 绘杂点
                imagestring($this->_img, 5, mt_rand(-10, $this->imageW), mt_rand(-10, $this->imageH), $codeSet[mt_rand(0, 29)], $noiseColor);
            }
        }
    }

    /**
     * 绘制验证码干扰线
     * 
     * 画一条由两条连在一起构成的随机正弦函数曲线作干扰线(你可以改成更帅的曲线函数)
     * 高中的数学公式咋都忘了涅，写出来
     *  正弦型函数解析式：y=Asin(ωx+φ)+b
     * 各常数值对函数图像的影响：
     *  A：决定峰值（即纵向拉伸压缩的倍数）
     *  b：表示波形在Y轴的位置关系或纵向移动距离（上加下减）
     *  φ：决定波形与X轴位置关系或横向移动距离（左加右减）
     *  ω：决定周期（最小正周期T=2π/∣ω∣）
     *
     * @return void
     */
    protected function writeCurve()
    {
        $px = $py = 0;
        // 曲线前部分
        $A = mt_rand(1, $this->imageH / 2); // 振幅
        $b = mt_rand(-$this->imageH / 4, $this->imageH / 4); // Y轴方向偏移量
        $f = mt_rand(-$this->imageH / 4, $this->imageH / 4); // X轴方向偏移量
        $T = mt_rand($this->imageH, $this->imageW * 2); // 周期
        $w = (2 * M_PI) / $T;

        $px1 = 0; // 曲线横坐标起始位置
        $px2 = mt_rand($this->imageW / 2, intval($this->imageW * 0.8)); // 曲线横坐标结束位置

        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if (0 != $w) {
                $py = $A * sin($w * $px + $f) + $b + $this->imageH / 2; // y = Asin(ωx+φ) + b
                $i  = ($this->fontSize / 5);
                while ($i > 0) {
                    // 这里(while)循环画像素点比imagettftext和imagestring用字体大小一次画出（不用这while循环）性能要好很多
                    imagesetpixel($this->_img, intval($px + $i), intval($py + $i), $this->_color);
                    $i--;
                }
            }
        }

        // 曲线后部分
        $A   = mt_rand(1, $this->imageH / 2); // 振幅
        $f   = mt_rand(-$this->imageH / 4, $this->imageH / 4); // X轴方向偏移量
        $T   = mt_rand($this->imageH, $this->imageW * 2); // 周期
        $w   = (2 * M_PI) / $T;
        $b   = $py - $A * sin($w * $px + $f) - $this->imageH / 2;
        $px1 = $px2;
        $px2 = $this->imageW;

        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if (0 != $w) {
                $py = $A * sin($w * $px + $f) + $b + $this->imageH / 2; // y = Asin(ωx+φ) + b
                $i  = ($this->fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($this->_img, intval($px + $i), intval($py + $i), $this->_color);
                    $i--;
                }
            }
        }
    }
}
