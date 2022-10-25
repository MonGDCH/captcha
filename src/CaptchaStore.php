<?php

namespace mon\captcha;

/**
 * 自定义验证码数据存储接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface CaptchaStore
{
    /**
     * 获取存储数据
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * 设置存储数据
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value);

    /**
     * 删除存储数据
     *
     * @param string $key
     * @return mixed
     */
    public function delete($key);
}
