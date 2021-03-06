<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3458a0d8d05118ddf8fc679b73278f4b
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\EventDispatcher\\' => 34,
        ),
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\EventDispatcher\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/event-dispatcher',
        ),
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'Y' => 
        array (
            'Yandex\\Tests' => 
            array (
                0 => __DIR__ . '/..' . '/nixsolutions/yandex-php-library/tests',
            ),
            'Yandex' => 
            array (
                0 => __DIR__ . '/..' . '/nixsolutions/yandex-php-library/src',
            ),
        ),
        'G' => 
        array (
            'Guzzle\\Stream' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/stream',
            ),
            'Guzzle\\Service' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/service',
            ),
            'Guzzle\\Parser' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/parser',
            ),
            'Guzzle\\Inflection' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/inflection',
            ),
            'Guzzle\\Http' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/http',
            ),
            'Guzzle\\Common' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/common',
            ),
            'Guzzle\\Cache' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/cache',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3458a0d8d05118ddf8fc679b73278f4b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3458a0d8d05118ddf8fc679b73278f4b::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit3458a0d8d05118ddf8fc679b73278f4b::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
