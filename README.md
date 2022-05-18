# mon-captcha
php验证码库

支持数字、英文字符、数字计算、中文等多种图片验证码类型
支持自定义验证码存储，不依赖 session

#### 安装

```bash
composer require mongdch/mon-captcha
```

#### API文档

[请查看Wiki](https://github.com/MonGDCH/mon-captcha/wiki) 


#### 版本

##### 1.2.2

* 修正使用`__set`魔术方法设置`store`自定义存储引擎无效的BUG

##### 1.2.1

* 优化代码
* 增加 `CaptchaStore` 存储接口类

##### 1.2.0

* 优化验证码输出方式，支持任意框架

##### 1.1.1

* 增强对LAF框架的支持

##### 1.1.0

* 优化代码，增加验证码类型

##### 1.0.0

* 发布第一个版本
