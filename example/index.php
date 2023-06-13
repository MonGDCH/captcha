<?php

use mon\captcha\Captcha;

require __DIR__ . '/../vendor/autoload.php';

session_start();

// 所属应用
$app = 'test';
// 验证码id
$id = '';
// 是否直接输出
$output = false;

// 创建验证码实例
$captcha = new Captcha();
// 生成验证码图片
$img = $captcha->create($app, $id, $output);
// var_dump($img);

// 获取验证码信息
$codeInfo = $captcha->getCode($app, $id);
var_dump($codeInfo);

// 校验验证码
$check = $captcha->check($codeInfo['code'], $app, $id);
var_dump($check);