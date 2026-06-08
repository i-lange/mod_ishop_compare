<?php

declare(strict_types=1);

namespace Tests\Php\Unit;

use Ilange\Module\Ishopcompare\Site\Dispatcher\Dispatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Php\Support\FakeApp;
use Tests\Php\Support\FakeCompareModel;
use Tests\Php\Support\FakeComponent;
use Tests\Php\Support\FakeDocument;
use Tests\Php\Support\FakeMVCFactory;
use Tests\Php\Support\FakeWebAssetManager;
use Tests\Php\Support\ParameterBag;

#[Group('unit')]
final class DispatcherTest extends TestCase
{
    /**
     * Проверяет сохранение базовых layout data от родителя.
     */
    public function testItKeepsParentLayoutData(): void
    {
        [$dispatcher, $module, $app, $input, $params, $template] = $this->createDispatcher(5);
        $data = $dispatcher->exposeLayoutData();

        self::assertSame($module, $data['module']);
        self::assertSame($app, $data['app']);
        self::assertSame($input, $data['input']);
        self::assertSame($params, $data['params']);
        self::assertSame($template, $data['template']);
    }

    /**
     * Проверяет получение Web Asset Manager и регистрацию asset registry file.
     */
    public function testItRegistersWebAssetManifest(): void
    {
        [$dispatcher, , $app, , , , , $document, $webAssetManager] = $this->createDispatcher(1);
        $data = $dispatcher->exposeLayoutData();

        self::assertSame(1, $app->getDocumentCalls);
        self::assertSame(1, $document->getWebAssetManagerCalls);
        self::assertSame(['media/mod_ishop_compare/joomla.asset.json'], $data['wa']->registry->registryFiles);
        self::assertSame($webAssetManager, $data['wa']);
    }

    /**
     * Проверяет bootstrapping com_ishop и создание Compare-модели.
     */
    #[DataProvider('countProvider')]
    public function testItLoadsCompareCount(mixed $count): void
    {
        [$dispatcher, , $app, , , , $mvcFactory] = $this->createDispatcher($count);
        $data = $dispatcher->exposeLayoutData();

        self::assertSame(['com_ishop'], $app->bootComponentCalls);
        self::assertSame([['Compare', 'Site']], $mvcFactory->createModelCalls);
        self::assertSame($count, $data['count']);
    }

    /**
     * Возвращает значения count для проверки передачи в layout data.
     */
    public static function countProvider(): array
    {
        return [[0], [1], [5]];
    }

    /**
     * Фиксирует текущее поведение при ошибке bootComponent(): exception пробрасывается.
     */
    public function testItPropagatesBootComponentException(): void
    {
        [$dispatcher, , $app] = $this->createDispatcher(1);
        $app->bootComponentException = new \RuntimeException('com_ishop unavailable');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('com_ishop unavailable');

        $dispatcher->exposeLayoutData();
    }

    /**
     * Фиксирует текущее поведение при ошибке createModel(): exception пробрасывается.
     */
    public function testItPropagatesCreateModelException(): void
    {
        [$dispatcher, , , , , , $mvcFactory] = $this->createDispatcher(1);
        $mvcFactory->createModelException = new \RuntimeException('Compare model unavailable');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Compare model unavailable');

        $dispatcher->exposeLayoutData();
    }

    /**
     * Собирает dispatcher с doubles для цепочки Joomla application.
     */
    private function createDispatcher(mixed $count): array
    {
        $webAssetManager = new FakeWebAssetManager();
        $document = new FakeDocument($webAssetManager);
        $model = new FakeCompareModel($count);
        $mvcFactory = new FakeMVCFactory($model);
        $component = new FakeComponent($mvcFactory);
        $app = new FakeApp($document, $component);
        $params = new ParameterBag();
        $module = (object) ['id' => 12, 'params' => $params];
        $input = new \stdClass();
        $template = new \stdClass();
        $dispatcher = new TestableDispatcher($module, $app, $input, $params, $template);

        return [$dispatcher, $module, $app, $input, $params, $template, $mvcFactory, $document, $webAssetManager];
    }
}

final class TestableDispatcher extends Dispatcher
{
    /**
     * Открывает protected getLayoutData() только для unit-теста.
     */
    public function exposeLayoutData(): array
    {
        return $this->getLayoutData();
    }
}
