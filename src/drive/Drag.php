<?php

declare(strict_types=1);

namespace mon\captcha\drive;

use mon\captcha\CaptchaDrive;
use mon\captcha\CaptchaInfo;
use RuntimeException;

/**
 * 拖拽验证码驱动
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Drag implements CaptchaDrive
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 背景素材
        'imgs'          => [
            __DIR__ . '/../drag/imgs/1.jpeg',
            __DIR__ . '/../drag/imgs/2.jpeg',
            __DIR__ . '/../drag/imgs/3.jpeg',
            __DIR__ . '/../drag/imgs/4.jpeg',
            __DIR__ . '/../drag/imgs/5.jpeg',
        ],
        // 生成验证码图片宽度
        'bg_width'      => 300,
        // 生成验证码图片高度
        'bg_height'     => 160,
        // 浮块图片
        'mark_img'      => __DIR__ . '/../drag/mark/img.png',
        // 浮块背景图片
        'mark_bg'       => __DIR__ . '/../drag/mark/bg.png',
        // 拖拽标志宽度
        'mark_width'    => 50,
        // 拖拽标志高度
        'mark_height'   => 50
    ];

    /**
     * 合成生成验证码图片资源
     *
     * @var mixed
     */
    protected $im = null;

    /**
     * 原始背景图片资源
     *
     * @var mixed
     */
    protected $im_fullbg = null;

    /**
     * 背景图片资源
     *
     * @var mixed
     */
    protected $im_bg = null;

    /**
     * 拖拽浮块资源
     *
     * @var mixed
     */
    protected $im_slide = null;

    /**
     * X轴偏移值
     *
     * @var integer
     */
    protected $_x = 0;

    /**
     * Y轴偏移值
     *
     * @var integer
     */
    protected $_y = 0;

    /**
     * 设置配置信息
     *
     * @param array $config
     * @return Drag
     */
    public function setConfig(array $config): Drag
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
     * 创建验证码
     *
     * @return CaptchaInfo
     */
    public function create(): CaptchaInfo
    {
        // 创建图像
        $this->init();
        $this->createSlide();
        $this->createBg();
        $this->merge();

        // 获取输出图像
        ob_start();
        imagepng($this->im);
        $img = ob_get_clean();
        // 清空图片资源
        imagedestroy($this->im);
        imagedestroy($this->im_fullbg);
        imagedestroy($this->im_bg);
        imagedestroy($this->im_slide);

        // 横向拖动，用 x 作为验证值
        $code = $this->_x;

        return new CaptchaInfo($img, (string)$code);
    }

    /**
     * 校验验证码
     *
     * @param string $code   用户输入验证码
     * @param string $checkCode  创建的验证码
     * @param integer $fault  校验容错率，越大体验越好，越小破解难度越高
     * @return boolean
     */
    public function check(string $code, string $checkCode, int $fault = 3): bool
    {
        if (!is_numeric($code) || !is_numeric($checkCode)) {
            return false;
        }
        return abs($checkCode - $code) <= $fault;
    }

    /**
     * 初始化
     *
     * @return void
     */
    protected function init()
    {
        // 获取随机原背景图片
        $bg = array_rand($this->config['imgs']);
        $file_bg = $this->config['imgs'][$bg];
        if (!file_exists($file_bg)) {
            throw new RuntimeException('背景图片不存在！filename => ' . $file_bg);
        }

        // 获取图像信息
        $info = getimagesize($file_bg);
        $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        // 读取图片
        // $this->im_fullbg = imagecreatefrompng($file_bg);
        $this->im_fullbg = call_user_func($fun, $file_bg);
        // 创建新图像
        $img = imagecreatetruecolor($this->config['bg_width'], $this->config['bg_height']);
        // 调整默认颜色
        $color = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $color);
        // 裁剪
        imagecopyresampled($img, $this->im_fullbg, 0, 0, 0, 0, $this->config['bg_width'], $this->config['bg_height'], $info[0], $info[1]);
        // 销毁原图
        imagedestroy($this->im_fullbg);
        // 设置新图像
        $this->im_fullbg = $img;
        // 按配置大小截取图片
        $this->im_bg = imagecreatetruecolor($this->config['bg_width'], $this->config['bg_height']);
        imagecopy($this->im_bg, $this->im_fullbg, 0, 0, 0, 0, $this->config['bg_width'], $this->config['bg_height']);

        // 生成浮块随机偏移值
        $this->_x = mt_rand(50, $this->config['bg_width'] - $this->config['mark_width'] - 1);
        $this->_y = mt_rand(0, $this->config['bg_height'] - $this->config['mark_height'] - 1);
        // 记录偏移值，用于验证
        // $this->setCode($this->getKey($id), ['offset_x' => $this->_x, 'offset_y' => $this->_y, 'time' => time()]);
    }

    /**
     * 绘制浮块
     *
     * @return void
     */
    protected function createSlide()
    {
        // 创建浮块标准图句柄
        $this->im_slide = imagecreatetruecolor($this->config['mark_width'], $this->config['bg_height']);
        $file_mark = $this->config['mark_img'];
        if (!file_exists($file_mark)) {
            throw new RuntimeException('浮块图片不存在！filename => ' . $file_mark);
        }

        // 获取图片信息
        $info = getimagesize($file_mark);
        // 读取图片
        $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $img_mark = call_user_func($fun, $file_mark);
        // 创建新图像
        $img = imagecreatetruecolor($this->config['mark_width'], $this->config['mark_height']);
        // 调整默认颜色
        $color = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $color);
        // 裁剪
        imagecopyresampled($img, $img_mark, 0, 0, 0, 0, $this->config['mark_width'], $this->config['mark_height'], $info[0], $info[1]);
        // 销毁原图
        imagedestroy($img_mark);
        // 设置新图像
        $img_mark = $img;

        imagecopy($this->im_slide, $this->im_fullbg, 0, $this->_y, $this->_x, $this->_y, $this->config['mark_width'], $this->config['mark_height']);
        imagecopy($this->im_slide, $img_mark, 0, $this->_y, 0, 0, $this->config['mark_width'], $this->config['mark_height']);
        imagecolortransparent($this->im_slide, 0);
        imagedestroy($img_mark);
    }

    /**
     * 绘制浮块背景
     *
     * @return void
     */
    protected function createBg()
    {
        $file_mark = $this->config['mark_bg'];
        if (!file_exists($file_mark)) {
            throw new RuntimeException('浮块背景图片不存在！filename => ' . $file_mark);
        }

        // 获取图片信息
        $info = getimagesize($file_mark);
        // 读取图片
        $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $im = call_user_func($fun, $file_mark);
        // 创建新图像
        $img = imagecreatetruecolor($this->config['mark_width'], $this->config['mark_height']);
        // 调整默认颜色
        $color = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $color);
        // 裁剪
        imagecopyresampled($img, $im, 0, 0, 0, 0, $this->config['mark_width'], $this->config['mark_height'], $info[0], $info[1]);
        // 销毁原图
        imagedestroy($im);
        // 设置新图像
        $im = $img;

        imagecolortransparent($im, 0);
        imagecopy($this->im_bg, $im, $this->_x, $this->_y, 0, 0, $this->config['mark_width'], $this->config['mark_height']);
        imagedestroy($im);
    }

    /**
     * 合并图片
     *
     * @return void
     */
    protected function merge()
    {
        $this->im = imagecreatetruecolor($this->config['bg_width'], $this->config['bg_height'] * 3);
        imagecopy($this->im, $this->im_bg, 0, 0, 0, 0, $this->config['bg_width'], $this->config['bg_height']);
        imagecopy($this->im, $this->im_slide, 0, $this->config['bg_height'], 0, 0, $this->config['mark_width'], $this->config['bg_height']);
        imagecopy($this->im, $this->im_fullbg, 0, $this->config['bg_height'] * 2, 0, 0, $this->config['bg_width'], $this->config['bg_height']);
        imagecolortransparent($this->im, 0);
    }
}
