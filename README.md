# iimage
简单的图片上传工具

## 依赖关系

- [intervention/image](https://github.com/Intervention/image)

- [qiniu/php-sdk](https://github.com/qiniu/php-sdk)

## 使用方法

#### 安装扩展包

```bash
composer require nomandia/iimage "1.0.*"
```

#### 发布配置文件

如果默认的配置文件可以满足，跳过这步也可以，如果不满足，需要自定义配置，可以执行下面命令，然后到 config/bro_image.php 中去修改

```bash
php artisan vendor:publish --provider="Nomandia\IImage\IImageProvider"
```

## 使用

```php
// 注意要引入命名空间
$image = new IImage();

$config = [
    'folder'      => 'avatar',
    'filePrefix' => 'avatar_',
    'maxWidth'   => 260,
];

// $file 是 Laravel 的 Illuminate\Http\UploadedFile 对象 ,可以通过 Request 直接得到

$file = $request->avatar;
// 也可以通过下面方式
$file = $request->file('avatar');

$info = $image->upload($file, $config);

// 也可以使用链式操作去配置
$info = $image->setConfig($config)->upload($file);
```

具体的参数可以查看默认配置文件，里面有详细的介绍

## 将图片上传到七牛云

图片默认是上传到本地的，如果是上传到七牛云需要对下面内容进行配置
因为都是敏感信息，所以将其放到了 .env 中，因为这个文件是不需要上传到 github 的

__.env__

```bash
# 七牛的 access key 和 secret key 可以到个人中心的密钥管理中获得
QINIU_ACCESS_KEY=
QINIU_SECRET_KEY=
# bucket 就是对象存储的存储空间的名称
QINIU_BUCKET=
# 这里是七牛的 CDN 融合加速域名，可以在存储空间中找到，如我的就是 http://image.broqiang.com
QINIU_DOMAIN=
```
__iimage.php_

```php
'upload_type'      => 'local',
// 改为
'upload_type'      => 'qiniu',
```
