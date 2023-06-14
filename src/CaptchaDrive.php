<?php

declare(strict_types=1);

namespace mon\captcha;

/**
 * 验证码驱动接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface CaptchaDrive
{
    /**
     * 设置配置信息
     *
     * @param array $config
     * @return CaptchaDrive;
     */
    public function setConfig(array $config): CaptchaDrive;

    /**
     * 获取配置信息
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * 创建验证码
     *
     * @return CaptchaInfo
     */
    public function create(): CaptchaInfo;

    /**
     * 校验验证码
     *
     * @param string $code   用户输入验证码
     * @param string $checkCode  创建的验证码
     * @return boolean
     */
    public function check(string $code, string $checkCode): bool;
}
