<?php

declare(strict_types=1);

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
     * @param string $app    验证码所属应用
     * @param string $id     验证码ID
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $app, string $id, $default = null);

    /**
     * 设置存储数据
     *
     * @param string $app   验证码所属应用
     * @param string $id    验证码ID
     * @param mixed $value  值
     * @return mixed
     */
    public function set(string $app, string $id, $value);

    /**
     * 删除存储数据
     *
     * @param string $app   验证码所属应用
     * @param string $id    验证码ID
     * @return mixed
     */
    public function delete(string $app, string $id);
}
