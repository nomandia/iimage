<?php

namespace Nomandia\IImage;

use Illuminate\Support\ServiceProvider;

class IImageProvider extends ServiceProvider
{
    public function boot()
    {
        if (!file_exists(config_path('iimage.php'))) {
            $this->publishes([
                dirname(__DIR__) . '/config/iimage.php' => config_path('iimage.php'),
            ], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/iimage.php', 'iimage'
        );
    }
}