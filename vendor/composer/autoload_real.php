<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit31b06ab443d024f87bc98d1c70863dca
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit31b06ab443d024f87bc98d1c70863dca', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader();
        spl_autoload_unregister(array('ComposerAutoloaderInit31b06ab443d024f87bc98d1c70863dca', 'loadClassLoader'));

        $useStaticLoader = PHP_VERSION_ID >= 50600 && !defined('HHVM_VERSION') && (!function_exists('zend_loader_file_encoded') || !zend_loader_file_encoded());
        if ($useStaticLoader) {
            require_once __DIR__ . '/autoload_static.php';

            call_user_func(\Composer\Autoload\ComposerStaticInit31b06ab443d024f87bc98d1c70863dca::getInitializer($loader));
        } else {
            $classMap = require __DIR__ . '/autoload_classmap.php';
            if ($classMap) {
                $loader->addClassMap($classMap);
            }
        }

        $loader->setClassMapAuthoritative(true);
        if (method_exists($loader, 'setApcuPrefix')) { $loader->setApcuPrefix('US3HM8/cqQhDB+DRK+0Ou'); }
        $loader->register(true);

        if ($useStaticLoader) {
            $includeFiles = Composer\Autoload\ComposerStaticInit31b06ab443d024f87bc98d1c70863dca::$files;
        } else {
            $includeFiles = require __DIR__ . '/autoload_files.php';
        }
        foreach ($includeFiles as $fileIdentifier => $file) {
            composerRequire31b06ab443d024f87bc98d1c70863dca($fileIdentifier, $file);
        }

        return $loader;
    }
}

function composerRequire31b06ab443d024f87bc98d1c70863dca($fileIdentifier, $file)
{
    if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier])) {
        require $file;

        $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;
    }
}
