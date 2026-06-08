<?php

declare(strict_types=1);

namespace Tests\Php\Support;

/**
 * Параметры модуля с тем же методом get(), который использует layout.
 */
final class ParameterBag
{
    public function __construct(private array $values = [])
    {
    }

    /**
     * Возвращает значение параметра или default, как Joomla Registry.
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->values[$name] ?? $default;
    }
}

/**
 * Registry double для проверки addRegistryFile().
 */
final class FakeWebAssetRegistry
{
    public array $registryFiles = [];
    public array $extensionRegistryFiles = [];

    /**
     * Записывает путь registry file, который регистрирует dispatcher.
     */
    public function addRegistryFile(string $file): void
    {
        $this->registryFiles[] = $file;
    }

    /**
     * Поддерживает актуальный Joomla API для возможных будущих изменений.
     */
    public function addExtensionRegistryFile(string $extension): void
    {
        $this->extensionRegistryFiles[] = $extension;
    }
}

/**
 * WebAssetManager double для layout и dispatcher тестов.
 */
final class FakeWebAssetManager
{
    public FakeWebAssetRegistry $registry;
    public array $scripts = [];
    public array $styles = [];

    public function __construct()
    {
        $this->registry = new FakeWebAssetRegistry();
    }

    /**
     * Возвращает registry double.
     */
    public function getRegistry(): FakeWebAssetRegistry
    {
        return $this->registry;
    }

    /**
     * Записывает подключенные script assets.
     */
    public function useScript(string $asset): void
    {
        $this->scripts[] = $asset;
    }

    /**
     * Записывает подключенные style assets.
     */
    public function useStyle(string $asset): void
    {
        $this->styles[] = $asset;
    }
}

/**
 * Document double с подсчетом обращений к WebAssetManager.
 */
final class FakeDocument
{
    public int $getWebAssetManagerCalls = 0;

    public function __construct(private FakeWebAssetManager $webAssetManager)
    {
    }

    /**
     * Возвращает один и тот же WebAssetManager double.
     */
    public function getWebAssetManager(): FakeWebAssetManager
    {
        $this->getWebAssetManagerCalls++;

        return $this->webAssetManager;
    }
}

/**
 * Compare model double с настраиваемым количеством товаров.
 */
final class FakeCompareModel
{
    public function __construct(private mixed $count)
    {
    }

    /**
     * Возвращает count, который dispatcher должен передать в layout data.
     */
    public function getCount(): mixed
    {
        return $this->count;
    }
}

/**
 * MVC factory double для проверки createModel().
 */
final class FakeMVCFactory
{
    public array $createModelCalls = [];
    public ?\Throwable $createModelException = null;

    public function __construct(private FakeCompareModel $compareModel)
    {
    }

    /**
     * Создает Compare model или бросает заданное исключение.
     */
    public function createModel(string $name, string $client): FakeCompareModel
    {
        $this->createModelCalls[] = [$name, $client];

        if ($this->createModelException !== null) {
            throw $this->createModelException;
        }

        return $this->compareModel;
    }
}

/**
 * Component double для цепочки app->bootComponent()->getMVCFactory().
 */
final class FakeComponent
{
    public int $getMVCFactoryCalls = 0;

    public function __construct(private FakeMVCFactory $mvcFactory)
    {
    }

    /**
     * Возвращает MVC factory double.
     */
    public function getMVCFactory(): FakeMVCFactory
    {
        $this->getMVCFactoryCalls++;

        return $this->mvcFactory;
    }
}

/**
 * Application double для dispatcher и installer script.
 */
final class FakeApp
{
    public int $getDocumentCalls = 0;
    public array $bootComponentCalls = [];
    public array $messages = [];
    public ?\Throwable $bootComponentException = null;

    public function __construct(
        private FakeDocument $document,
        private FakeComponent $component
    ) {
    }

    /**
     * Возвращает document double.
     */
    public function getDocument(): FakeDocument
    {
        $this->getDocumentCalls++;

        return $this->document;
    }

    /**
     * Возвращает component double или фиксирует ошибку зависимости.
     */
    public function bootComponent(string $name): FakeComponent
    {
        $this->bootComponentCalls[] = $name;

        if ($this->bootComponentException !== null) {
            throw $this->bootComponentException;
        }

        return $this->component;
    }

    /**
     * Записывает сообщения installer script вместо вывода Joomla UI.
     */
    public function enqueueMessage(string $message, string $type): void
    {
        $this->messages[] = [$message, $type];
    }
}

/**
 * Результат выполнения layout с HTML и списком подключенных assets.
 */
final class LayoutRenderResult
{
    public function __construct(
        public string $html,
        public FakeWebAssetManager $webAssetManager
    ) {
    }
}

/**
 * Выполняет tmpl/default.php в изолированном наборе переменных без Joomla CMS.
 */
final class LayoutRenderer
{
    /**
     * Рендерит layout и возвращает HTML вместе с WebAssetManager double.
     */
    public function render(array $paramOverrides = [], mixed $count = 0): LayoutRenderResult
    {
        \Joomla\CMS\Language\Text::reset();
        \Joomla\CMS\Router\Route::reset();
        \Ilange\Component\Ishop\Site\Helper\RouteHelper::reset();

        $webAssetManager = new FakeWebAssetManager();
        $defaults = [
            'use_js' => 1,
            'use_css' => 1,
            'show_text' => 1,
            'show_count' => 1,
            'show_btn_clear' => 1,
        ];

        $params = new ParameterBag(array_replace($defaults, $paramOverrides));
        $module = (object) ['id' => $paramOverrides['module_id'] ?? 101, 'params' => $params];
        $app = new \stdClass();
        $input = new \stdClass();
        $template = new \stdClass();
        $wa = $webAssetManager;

        ob_start();
        include dirname(__DIR__, 3) . '/tmpl/default.php';
        $html = (string) ob_get_clean();

        return new LayoutRenderResult($html, $webAssetManager);
    }
}

/**
 * Утилита чтения language-файлов Joomla с поиском дублей.
 */
final class IniFileReader
{
    /**
     * Возвращает ключи и дубли без интерпретации переводов.
     */
    public static function read(string $file): array
    {
        $keys = [];
        $duplicates = [];

        foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, ';') || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (!str_contains($trimmed, '=')) {
                continue;
            }

            [$key] = explode('=', $trimmed, 2);
            $key = trim($key);

            if (isset($keys[$key])) {
                $duplicates[] = $key;
            }

            $keys[$key] = true;
        }

        return [
            'keys' => array_keys($keys),
            'duplicates' => $duplicates,
        ];
    }
}
