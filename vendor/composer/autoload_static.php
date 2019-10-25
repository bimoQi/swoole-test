<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit05310f51bbd8e3be64fa9fa4ac68ce71
{
    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'Google\\Protobuf\\' => 16,
            'GPBMetadata\\Google\\Protobuf\\' => 28,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Google\\Protobuf\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/protobuf/src/Google/Protobuf',
        ),
        'GPBMetadata\\Google\\Protobuf\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/protobuf/src/GPBMetadata/Google/Protobuf',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit05310f51bbd8e3be64fa9fa4ac68ce71::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit05310f51bbd8e3be64fa9fa4ac68ce71::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}