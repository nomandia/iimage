# iimage
Simple picture upload toolkit.

## 准备工作

### 安装
```bash
composer require nomandia/iimage
```

### 发布配置
```bash
php artisan vendor:publish --provider="Nomandia\IImage\IImageProvider"
```

## 使用

### 一个栗子
```php
// 注意要引入命名空间
$image = new IImage();

// 支持动态传入配置文件，所有配置文件中的配置都可以传入，在第二个参数中传入一个数组即可，
// key 和配置文件中的 key 相同即可
$config = [
    'folder'      => 'avatar',
    'file_prefix' => 'avatar_',
    'max_width'   => 260,
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