<?php

use mon\captcha\CaptchaStore;

require __DIR__ . '/../vendor/autoload.php';

class Store implements CaptchaStore
{
    /**
     * 缓存数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 获取存储数据
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * 设置存储数据
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * 删除存储数据
     *
     * @param string $key
     * @return mixed
     */
    public function del($key)
    {
        unset($this->data[$key]);
    }
}
