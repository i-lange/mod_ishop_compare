<?php

declare(strict_types=1);

/**
 * Автономный bootstrap тестов: он не поднимает Joomla CMS, com_installer,
 * реальный application context, настоящую базу данных, CMS Web Asset Manager
 * и компонент com_ishop.
 */

defined('_JEXEC') || define('_JEXEC', 1);

$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/stubs/JoomlaStubs.php';
require_once __DIR__ . '/Support/TestDoubles.php';

spl_autoload_register(
    /**
     * Подключает классы текущего модуля напрямую из src без production Composer autoload.
     */
    static function (string $class): void {
        $prefix = 'Ilange\\Module\\Ishopcompare\\';
        $root = dirname(__DIR__, 2) . '/src/';

        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            $relative = preg_replace('#^Site/#', '', $relative);
            $file = $root . $relative;

            if (is_file($file)) {
                require_once $file;
            }
        }
    }
);

spl_autoload_register(
    /**
     * Подключает support-классы тестов из tests/php/Support.
     */
    static function (string $class): void {
        $prefix = 'Tests\\Php\\Support\\';
        $root = __DIR__ . '/Support/';

        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            $file = $root . $relative;

            if (is_file($file)) {
                require_once $file;
            }
        }
    }
);
