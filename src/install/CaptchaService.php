<?php

declare(strict_types=1);

namespace support\captcha;

use mon\env\Config;
use mon\util\Instance;
use mon\captcha\Captcha;

/**
 * 验证码服务
 * 
 * @method \mon\captcha\CaptchaStore getStore()   获取验证码存储驱动
 * @method \mon\captcha\CaptchaDrive getDrive()   获取验证码生成驱动
 * @method \mon\captcha\CaptchaInfo create(string $app, string $id = '', ...$params)  创建验证码
 * @method boolean check(string $code, string $app, string $id = '', ...$params)  校验验证码
 * @method mixed getConfig(string $key = null, $default = null)    获取配置信息
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class CaptchaService
{
    use Instance;

    /**
     * 缓存服务对象
     *
     * @var Captcha
     */
    protected $service;

    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 验证器驱动
        'drive'     => [
            // 驱动对象名，实现 CaptchaDrive 接口
            'class'     => \mon\captcha\drive\Image::class,
            // 驱动配置信息
            'config'    => [],
        ],
        // 存储驱动实例，实现 CaptchaStore 接口
        'store'     => \mon\captcha\store\GaiaSession::class,
        // 验证码过期时间（s）
        'expire'    => 60,
        // 验证成功后是否重置验证码
        'reset'     => true,
    ];

    /**
     * 构造方法
     */
    protected function __construct()
    {
        $this->config = array_merge($this->config, Config::instance()->get('captcha', []));
        $this->service = new Captcha($this->config);
    }

    /**
     * 获取验证码库实例
     *
     * @return Captcha
     */
    public function getService(): Captcha
    {
        return $this->service;
    }

    /**
     * 回调服务
     *
     * @param string $name      方法名
     * @param mixed $arguments  参数列表
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->getService(), $name], (array) $arguments);
    }
}
