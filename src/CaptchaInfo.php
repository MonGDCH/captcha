<?php

declare(strict_types=1);

namespace mon\captcha;

/**
 * 生成验证码结果集对象
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
final class CaptchaInfo
{
    /**
     * 验证码图片
     *
     * @var string
     */
    private $img = '';

    /**
     * 验证码数据
     *
     * @var string
     */
    private $code;

    /**
     * 构造方法
     *
     * @param string $img   验证码图片
     * @param string $code  验证码数据
     */
    public function __construct(string $img, string $code)
    {
        $this->img = $img;
        $this->code = $code;
    }

    /**
     * 获取验证码数据
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * 获取验证码图片内容
     *
     * @return string
     */
    public function getImg(): string
    {
        return $this->img;
    }

    /**
     * 获取图片base64
     *
     * @return string
     */
    public function getBase64(): string
    {
        $content = chunk_split(base64_encode($this->getImg()));
        $base64 = 'data:image/png;base64,' . $content;
        return $base64;
    }

    /**
     * 输出验证码图像
     *
     * @return void
     */
    public function output()
    {
        ob_clean();
        header("Content-type: image/png");
        echo $this->getImg();
    }
}
