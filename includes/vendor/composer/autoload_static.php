<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit439b7fdec23e064c994878d92165a07e
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Printful\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Printful\\' => 
        array (
            0 => __DIR__ . '/..' . '/printful/php-api-sdk/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit439b7fdec23e064c994878d92165a07e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit439b7fdec23e064c994878d92165a07e::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
