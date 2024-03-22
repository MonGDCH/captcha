<?php

declare(strict_types=1);

namespace mon\captcha;

use mon\captcha\drive\Image;
use mon\captcha\store\Session;

/**
 * 验证码
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.2.0 优化定制支持实现，支持任意框架
 */
class Captcha
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 验证器驱动
        'drive'     => [
            // 驱动对象名，实现 CaptchaDrive 接口
            'class'     => Image::class,
            // 驱动配置信息
            'config'    => [],
        ],
        // 存储驱动实例，实现 CaptchaStore 接口
        'store'     => Session::class,
        // 验证码过期时间（s）
        'expire'    => 180,
        // 验证成功后是否重置验证码
        'reset'     => true,
    ];

    /**
     * 错误信息
     *
     * @var mixed
     */
    protected $error;

    /**
     * 缓存驱动实例
     *
     * @var CaptchaStore
     */
    protected $_store;

    /**
     * 验证码生成驱动
     *
     * @var CaptchaDrive
     */
    protected $_drive;

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
     * 获取错误信息
     *
     * @return mixed
     */
    public function getError()
    {
        $error = $this->error;
        $this->error = '';
        return $error;
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
     * 获取验证码存储驱动
     *
     * @return CaptchaStore
     */
    public function getStore()
    {
        if (is_null($this->_store)) {
            if (is_string($this->config['store'])) {
                // 字符串类名，new一个驱动实例
                $this->_store = new $this->config['store'];
            } else {
                // 非类名，认定为存储驱动实例
                $this->_store = $this->config['store'];
            }
        }

        return $this->_store;
    }

    /**
     * 获取验证码生成驱动
     *
     * @return CaptchaDrive
     */
    public function getDrive(): CaptchaDrive
    {
        if (is_null($this->_drive)) {
            $drive = $this->config['drive']['class'] ?? Image::class;
            $config = $this->config['drive']['config'] ?? [];
            $this->_drive = new $drive;
            $this->_drive->setConfig($config);
        }

        return $this->_drive;
    }

    /**
     * 创建验证码
     *
     * @param string $app 验证码所属应用
     * @param string $id 验证码ID
     * @param mixed ...$params  生成验证码参数
     * @return CaptchaInfo  验证码信息对象
     */
    public function create(string $app, string $id = '', ...$params): CaptchaInfo
    {
        // 创建验证码
        $captcha = $this->getDrive()->create(...$params);
        // 保存验证码
        $this->setCode($captcha->getCode(), $app, $id);

        return $captcha;
    }

    /**
     * 验证验证码
     *
     * @param string $code  验证码
     * @param string $app 验证码所属应用
     * @param string $id    验证码ID
     * @param mixed $params 额外参数
     * @return boolean
     */
    public function check(string $code, string $app, string $id = '', ...$params): bool
    {
        // 获取验证码
        $store = $this->getCode($app, $id);
        if (empty($store) || empty($code)) {
            $this->error = '验证码参数错误';
            return false;
        }
        // 验证有效期
        if (time() - $store['time'] > $this->config['expire']) {
            $this->deleteCode($app, $id);
            $this->error = '验证码已过期';
            return false;
        }
        // 验证验证码
        $check = $this->getDrive()->check($code, $store['code'], ...$params);
        if (!$check) {
            $this->error = '验证码错误';
            return false;
        }

        // 清除已使用验证码
        $this->config['reset'] && $this->deleteCode($app, $id);
        return true;
    }

    /**
     * 保存验证码
     *
     * @param string $code  验证码值
     * @param string $app 验证码所属应用
     * @param string $id 验证码ID
     * @return Captcha
     */
    public function setCode(string $code, string $app, string $id = ''): Captcha
    {
        $data = [
            'code' => $code,
            'time' => time(),
            'app'  => $app,
            'id'   => $id
        ];
        $this->getStore()->set($app, $id, $data);
        return $this;
    }

    /**
     * 获取验证码
     *
     * @param string $app 验证码所属应用
     * @param string $id 验证码ID
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getCode(string $app, string $id = '', $default = null)
    {
        return $this->getStore()->get($app, $id, $default);
    }

    /**
     * 删除验证码
     *
     * @param string $app 验证码所属应用
     * @param string $id 验证码ID
     * @return mixed
     */
    public function deleteCode(string $app, string $id = '')
    {
        return $this->getStore()->delete($app, $id);
    }
}
