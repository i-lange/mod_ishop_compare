<?php
/**
 * @package    mod_ishop_compare
 * @author     Pavel Lange <pavel@ilange.ru>
 * @link       https://github.com/i-lange/mod_ishop_compare
 * @copyright  (C) 2025 Pavel Lange <https://ilange.ru>
 * @license    GNU General Public License version 2 or later
 */

use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

defined('_JEXEC') or exit;

/**
 * Класс Service Provider
 * @since  1.0.0
 */
return new class implements ServiceProviderInterface
{
    /**
     * Регистрируем сервисы с помощью контейнера внедрения зависимостей
     * @param Container $container Контейнер DI
     * @since 1.0.0
     */
    public function register(Container $container)
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Ilange\\Module\\Ishopcompare'));
        $container->registerServiceProvider(new Module);
    }
};