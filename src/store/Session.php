<?php

declare(strict_types=1);

namespace mon\captcha\store;

use mon\captcha\CaptchaStore;

/**
 * Session存储驱动
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Session implements CaptchaStore
{
    /**
     * 获取存储数据
     *
     * @param string $app 验证码所属应用
     * @param string $id 验证码ID
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $app, string $id, $default = null)
    {
        $key = $this->encode($app, $id);
        return $_SESSION[$key] ?? $default;
    }

    /**
     * 设置存储数据
     *
     * @param string $app 验证码所属应用
     * @param string $id 验证码ID
     * @param mixed $value 值
     * @return mixed
     */
    public function set(string $app, string $id, $value)
    {
        $key = $this->encode($app, $id);
        $_SESSION[$key] = $value;
    }

    /**
     * 删除存储数据
     *
     * @param string $app 验证码所属应用
     * @param string $id 验证码ID
     * @return mixed
     */
    public function delete(string $app, string $id)
    {
        $key = $this->encode($app, $id);
        unset($_SESSION[$key]);
    }

    /**
     * 加密验证码
     *
     * @param string $str 验证码信息
     * @return string
     */
    protected function encode(string $app, string $id): string
    {
        return md5($app . $id);
    }
}
