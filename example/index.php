<?php

use mon\captcha\Captcha;
use mon\captcha\drive\Drag;

require __DIR__ . '/../vendor/autoload.php';

session_start();

// 所属应用
$app = 'test';
// 验证码id
$id = '';
// 是否直接输出
$output = false;

// 创建验证码实例
$captcha = new Captcha([
    // 'drive' => [
    // 'class' => Drag::class,
    // // 驱动配置信息
    // 'config'    => [],
    // ]
]);
// 生成验证码图片
$img = $captcha->create($app, $id, ['num', 'en', 'calcul', 'scalar'], 4);
$img->output();
// var_dump($img);

// // 获取验证码信息
// $codeInfo = $captcha->getCode($app, $id);
// var_dump($codeInfo);

// // 校验验证码
// $check = $captcha->check($img->getCode(), $app, $id);
// var_dump($check);
