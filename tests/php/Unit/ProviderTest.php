<?php

declare(strict_types=1);

namespace Tests\Php\Unit;

use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ProviderTest extends TestCase
{
    /**
     * Проверяет, что provider возвращает объект ServiceProviderInterface.
     */
    public function testProviderImplementsServiceProviderInterface(): void
    {
        $provider = require dirname(__DIR__, 3) . '/services/provider.php';

        self::assertInstanceOf(ServiceProviderInterface::class, $provider);
    }

    /**
     * Проверяет регистрацию dispatcher factory и module provider в правильном порядке.
     */
    public function testProviderRegistersExpectedServicesInOrder(): void
    {
        $provider = require dirname(__DIR__, 3) . '/services/provider.php';
        $container = new Container();

        $provider->register($container);

        self::assertCount(2, $container->registeredProviders);
        self::assertInstanceOf(ModuleDispatcherFactory::class, $container->registeredProviders[0]);
        self::assertSame('\\Ilange\\Module\\Ishopcompare', $container->registeredProviders[0]->namespace);
        self::assertInstanceOf(Module::class, $container->registeredProviders[1]);
    }
}
