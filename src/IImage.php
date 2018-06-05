<?php

namespace Nomandia\IImage;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class IImage
{
    protected $allowed_ext;

    protected $rootFolder;

    protected $folder;

    protected $filePrefix = '';

    protected $maxWidth = false;

    protected $uploadType = 'local';

    protected $dateFormat = 'Y/m/d';

    protected $maxSize;

    protected $isUrl;

    protected $url;

    protected $filename;

    protected $extension;

    protected $qiniuAccessKey;

    protected $qiniuSecretKey;

    protected $qiniuBucket;

    protected $qiniuDomain;

    protected $randomLength = 20;

    public function __construct()
    {
        $this->initConfig();
    }

    public function initConfig()
    {
        $configs = config('iimage', []);
        $this->setConfig($configs);
        if (array_key_exists('rootFolder', $configs)) {
            $this->rootFolder = DIRECTORY_SEPARATOR . trim($configs['rootFolder'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
    }

    public function upload($file, $config = [])
    {
        // 将自定义配置初始化
        $this->setConfig($config);
        switch ($this->uploadType) {
            case 'local':

                return $this->uploadLocal($file);
                break;
            case 'qiniu':
                return $this->uploadQiniu($file);
                break;
        }
    }

    /**
     * 上传文件到本地
     * @param  [type] $file [description]
     * @return [type] [description]
     */
    public function uploadLocal($file)
    {
        // 格式 uploads/image/$folder/2018/02/02/
        $folderName = $this->rootFolder . $this->folder . DIRECTORY_SEPARATOR . date($this->dateFormat, time());

        // 将 public 目录的绝对路径和 上面定义的路径拼接到一起
        // 格式: /www/www.broqiang.com/public/uploads/image/$folder/2018/02/02/
        $uploadPath = public_path($folderName);

        // 拼接文件名
        $filename = $this->getFilename($file);

        // 将图片移动到我们的目标存储路径中
        $file->move($uploadPath, $filename);

        if ($this->maxWidth && $this->extension != 'gif') {
            $this->reduceSize($uploadPath . DIRECTORY_SEPARATOR . $filename, $this->maxWidth);
        }

        return $this->getUrlPath($folderName, $filename);
    }

    /**
     * 上传到七牛云
     * 详细的内容见官方文档：https://developer.qiniu.com/kodo/sdk/1241/php
     * @param  [type] $file [description]
     * @return [type] [description]
     */
    public function uploadQiniu($file)
    {
        $info = [
            'success' => 1,
            'message' => '上传成功',
            'url' => '',
        ];

        $filename = $this->getFilename($file);

        $auth = new Auth($this->qiniuAccessKey, $this->qiniuSecretKey);
        $token = $auth->uploadToken($this->qiniuBucket);

        $upManager = new UploadManager();

        list($code, $error) = $upManager->putFile($token, $filename, $file);

        if ($error) {
            $info['success'] = 0;
            $info['message'] = $error->message();
            return $info;
        }

        $info['url'] = $this->qiniuDomain . DIRECTORY_SEPARATOR . $filename;
        return $info;
    }

    public function validateUpload(Request $request, $field)
    {
        $rules = [
            $field => 'bail|required|file|mimes:' . implode($this->allowed_ext, ',') . '|max:' . $this->maxSize,
        ];

        $messages = [
            $field . '.file' => '文件上传失败',
            $field . '.mimes' => '上传失败，文件类型不允许，请上传常规的图片 ' . implode($this->allowed_ext, ',') . '文件',
            $field . '.max' => '上传失败，文件过大，文件大小不得超出 ' . $this->maxSize . ' KB。',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $fail = [
                'success' => 0,
                'message' => $validator->errors()->first($field),
                'url' => '',
            ];
            return $fail;
        }
    }

    public function setConfig($config)
    {
        if (isset($config) && count($config)) {
            foreach ($config as $key => $value) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    public function getUrlPath($folderName, $filename)
    {
        $info = [
            'success' => 1,
            'message' => '上传成功',
            'url' => '',
        ];

        if ($this->isUrl) {
            $url = $this->url ?: config('app.url');
            $info['url'] = $url . $folderName . DIRECTORY_SEPARATOR . $filename;
            return $info;
        }

        $info['url'] = $folderName . DIRECTORY_SEPARATOR . $filename;

        return $info;
    }

    /**
     * 组合文件名
     * @return [type] [description]
     */
    public function getFilename($file)
    {
        // 获取文件的后缀名，因图片从剪贴板里黏贴时后缀名为空，所以此处确保后缀一直存在
        $this->extension = strtolower($file->getClientOriginalExtension()) ?: 'png';

        $randomName = str_random($this->randomLength);

        if ($this->filename) {
            return $this->filename . $this->extension;
        }

        if ($this->filePrefix) {
            return $this->filePrefix . '_' . $randomName . '.' . $this->extension;
        }

        return $randomName . '.' . $this->extension;
    }

    public function setFilename($filename = null)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * 将超过尺寸的图片等比缩放
     * @param  [type] $filePath [description]
     * @param  [type] $maxWidth [description]
     * @return [type] [description]
     */
    public function reduceSize($filePath, $maxWidth)
    {
        // 先实例化，传参是文件的磁盘物理路径
        $image = Image::make($filePath);

        // 进行大小调整的操作
        $image->resize($maxWidth, null, function ($constraint) {

            // 设定宽度是 $maxWidth，高度等比例缩放
            $constraint->aspectRatio();

            // 防止裁图时图片尺寸变大
            $constraint->upsize();
        });

        // 对图片修改后进行保存
        $image->save();
    }
}
