<?php

declare(strict_types=1);

namespace mon\captcha\store;

use InvalidArgumentException;
use mon\captcha\CaptchaStore;
use support\cache\CacheService;
use support\captcha\CaptchaService;

/**
 * Gaia框架缓存存储驱动
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class GaiaCache implements CaptchaStore
{
    /**
     * 获取存储数据
     *
     * @param string $app     验证码所属应用
     * @param string $id      验证码ID
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get(string $app, string $id, $default = null)
    {
        $key = $this->encode($app, $id);
        return CacheService::instance()->get($key, $default);
    }

    /**
     * 设置存储数据
     *
     * @param string $app   验证码所属应用
     * @param string $id    验证码ID
     * @param mixed  $value 值
     * @return mixed
     */
    public function set(string $app, string $id, $value)
    {
        $key = $this->encode($app, $id);
        $expire = CaptchaService::instance()->getConfig('expire', 60);
        return CacheService::instance()->set($key, $value, intval($expire));
    }

    /**
     * 删除存储数据
     *
     * @param string $app 验证码所属应用
     * @param string $id  验证码ID
     * @return mixed
     */
    public function delete(string $app, string $id)
    {
        $key = $this->encode($app, $id);
        return CacheService::instance()->delete($key);
    }

    /**
     * 获取验证码key名
     *
     * @param string $app   所属应用
     * @param string $id    验证码ID，不能为空
     * @return string
     */
    protected function encode(string $app, string $id): string
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Captcha id can not be empty');
        }
        return 'captcha::' . $app . '::' . $id;
    }
}
