<?php

use mon\captcha\Captcha;
use mon\captcha\drive\Drag;

session_start();

require __DIR__ . '/../vendor/autoload.php';


class App
{
    /**
     * 验证码所属应用
     *
     * @var string
     */
    protected $app = 'app';

    public function run()
    {
        $type = $_GET['page'] ?? 'index';
        $sdk = new Captcha([
            'drive' => [
                'class' => Drag::class,
                // 驱动配置信息
                // 'config'    => [],
            ]
        ]);
        switch ($type) {
            case 'captcha':
                $captcha = $sdk->create($this->app);
                $captcha->output();
                // var_dump($captcha);
                break;
            case 'check':
                $code = $_GET['code'] ?? '';
                $check = $sdk->check($code, $this->app);
                if ($check) {
                    $msg = json_encode(['code' => 1, 'msg' => '验证通过']);
                } else {
                    $msg = json_encode(['code' => 0, 'msg' => '验证不通过']);
                }
                header('Content-Type: application/json');
                echo $msg;
                break;
            default:
                include_once __DIR__ . '/drag.html';
                break;
        }
    }
}

(new App)->run();
