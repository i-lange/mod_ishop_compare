<?php

declare(strict_types=1);

/**
 * Минимальные stubs Joomla и com_ishop для автономных тестов модуля.
 * Они фиксируют только контракты, которые реально используются в коде.
 */

namespace Joomla\CMS;

final class Factory
{
    private static mixed $application = null;

    /**
     * Сохраняет тестовый application double для последующего Factory::getApplication().
     */
    public static function setApplication(mixed $application): void
    {
        self::$application = $application;
    }

    /**
     * Возвращает тестовое приложение без запуска Joomla application context.
     */
    public static function getApplication(): mixed
    {
        return self::$application;
    }
}

namespace Joomla\CMS\Dispatcher;

abstract class AbstractModuleDispatcher
{
    protected object $module;
    protected mixed $app;
    protected mixed $input;
    protected mixed $params;
    protected mixed $template;

    /**
     * Хранит базовые зависимости, которые реальный dispatcher получает от Joomla.
     */
    public function __construct(
        ?object $module = null,
        mixed $app = null,
        mixed $input = null,
        mixed $params = null,
        mixed $template = null
    ) {
        $this->module = $module ?? (object) ['params' => $params];
        $this->app = $app;
        $this->input = $input;
        $this->params = $params ?? ($this->module->params ?? null);
        $this->template = $template;
    }

    /**
     * Возвращает тот же набор ключей, который нужен layout модуля.
     */
    protected function getLayoutData()
    {
        return [
            'module' => $this->module,
            'app' => $this->app,
            'input' => $this->input,
            'params' => $this->params,
            'template' => $this->template,
        ];
    }
}

namespace Joomla\CMS\Extension\Service\Provider;

final class Module
{
    /**
     * Класс-маркер для проверки регистрации Joomla module provider.
     */
}

final class ModuleDispatcherFactory
{
    public string $namespace;

    /**
     * Сохраняет namespace, который provider передает фабрике dispatcher.
     */
    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }
}

namespace Joomla\CMS\Installer;

class InstallerAdapter
{
    private \SimpleXMLElement $manifest;

    /**
     * Хранит manifest, который installer script читает во время postflight().
     */
    public function __construct(?\SimpleXMLElement $manifest = null)
    {
        $this->manifest = $manifest ?? new \SimpleXMLElement(
            '<extension><name>mod_ishop_compare</name><version>1.0.0</version><author>Pavel Lange</author></extension>'
        );
    }

    /**
     * Возвращает manifest без обращения к реальному Joomla installer.
     */
    public function getManifest(): \SimpleXMLElement
    {
        return $this->manifest;
    }
}

class InstallerScript
{
    public static bool $preflightResult = true;
    public static int $removeFilesCalls = 0;

    /**
     * Сбрасывает счетчики parent-stub перед каждым тестом.
     */
    public static function reset(): void
    {
        self::$preflightResult = true;
        self::$removeFilesCalls = 0;
    }

    /**
     * Имитирует результат базового Joomla preflight().
     */
    public function preflight($type, $parent): bool
    {
        return self::$preflightResult;
    }

    /**
     * Фиксирует вызов удаления устаревших файлов без файловых операций.
     */
    protected function removeFiles(): void
    {
        self::$removeFilesCalls++;
    }
}

namespace Joomla\CMS\Language;

final class Text
{
    public static array $calls = [];
    public static array $translations = [];

    /**
     * Сбрасывает вызовы и тестовые переводы.
     */
    public static function reset(): void
    {
        self::$calls = [];
        self::$translations = [];
    }

    /**
     * Устанавливает предсказуемые переводы для тестов installer output.
     */
    public static function setTranslations(array $translations): void
    {
        self::$translations = $translations;
    }

    /**
     * Возвращает перевод ключа или сам ключ, как стабильное тестовое значение.
     */
    public static function _(string $key): string
    {
        self::$calls[] = $key;

        return self::$translations[$key] ?? $key;
    }
}

namespace Joomla\CMS\Router;

final class Route
{
    public static array $calls = [];

    /**
     * Сбрасывает список route-вызовов.
     */
    public static function reset(): void
    {
        self::$calls = [];
    }

    /**
     * Возвращает предсказуемый routed URL и запоминает исходный route.
     */
    public static function _(string $route): string
    {
        self::$calls[] = $route;

        return 'routed:' . $route;
    }
}

namespace Joomla\DI;

class Container
{
    public array $registeredProviders = [];

    /**
     * Записывает providers в порядке регистрации.
     */
    public function registerServiceProvider(object $provider): void
    {
        $this->registeredProviders[] = $provider;
    }
}

interface ServiceProviderInterface
{
    /**
     * Минимальный контракт service provider для tests/provider.php.
     */
    public function register(Container $container);
}

namespace Ilange\Component\Ishop\Site\Helper;

final class RouteHelper
{
    public static array $calls = [];
    public static string $route = 'index.php?option=com_ishop&view=compare';

    /**
     * Сбрасывает состояние route helper между layout-тестами.
     */
    public static function reset(): void
    {
        self::$calls = [];
        self::$route = 'index.php?option=com_ishop&view=compare';
    }

    /**
     * Возвращает маршрут страницы сравнения без загрузки com_ishop.
     */
    public static function getCompareRoute(): string
    {
        self::$calls[] = 'getCompareRoute';

        return self::$route;
    }
}
