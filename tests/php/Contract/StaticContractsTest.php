<?php

declare(strict_types=1);

namespace Tests\Php\Contract;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
final class StaticContractsTest extends TestCase
{
    private string $root;

    /**
     * Сохраняет корень проекта для статических contract-проверок.
     */
    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 3);
    }

    /**
     * Проверяет _JEXEC guards в PHP-файлах расширения.
     */
    public function testJexecGuardsExist(): void
    {
        foreach (['src/Dispatcher/Dispatcher.php', 'tmpl/default.php', 'services/provider.php', 'script.php'] as $file) {
            $contents = file_get_contents($this->root . '/' . $file);

            self::assertMatchesRegularExpression("/defined\\('_JEXEC'\\) or (die|exit);/", $contents, $file);
        }
    }

    /**
     * Проверяет синтаксис всех PHP-файлов проекта через текущий PHP.
     */
    public function testPhpSyntax(): void
    {
        foreach ($this->phpFiles() as $file) {
            $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file);
            exec($command, $output, $exitCode);

            self::assertSame(0, $exitCode, implode("\n", $output));
        }
    }

    /**
     * Проверяет XML syntax и обязательные элементы manifest.
     */
    public function testXmlSyntaxAndRequiredElements(): void
    {
        $xml = new \SimpleXMLElement(file_get_contents($this->root . '/mod_ishop_compare.xml'));

        foreach (['name', 'version', 'description', 'scriptfile'] as $element) {
            self::assertNotSame('', trim((string) $xml->{$element}), $element . ' is empty');
        }
    }

    /**
     * Проверяет JSON syntax для package, assets и tsconfig-файлов.
     */
    public function testJsonSyntax(): void
    {
        foreach (['package.json', 'media/joomla.asset.json', 'tsconfig.json', 'tsconfig.node.json'] as $file) {
            $decoded = json_decode(file_get_contents($this->root . '/' . $file), true, 512, JSON_THROW_ON_ERROR);

            self::assertIsArray($decoded);
        }
    }

    /**
     * Проверяет согласованность namespace в manifest, provider и dispatcher.
     */
    public function testNamespaceConsistency(): void
    {
        $manifest = file_get_contents($this->root . '/mod_ishop_compare.xml');
        $provider = file_get_contents($this->root . '/services/provider.php');
        $dispatcher = file_get_contents($this->root . '/src/Dispatcher/Dispatcher.php');

        self::assertStringContainsString('Ilange\Module\Ishopcompare', $manifest);
        self::assertStringContainsString('\\\\Ilange\\\\Module\\\\Ishopcompare', $provider);
        self::assertStringContainsString('namespace Ilange\Module\Ishopcompare\Site\Dispatcher;', $dispatcher);
    }

    /**
     * Фиксирует source-политику generated assets.
     */
    public function testGeneratedAssetsHaveSourcePolicy(): void
    {
        $readme = file_get_contents($this->root . '/README.md');

        self::assertStringContainsString('media/scss/front.scss', $readme);
        self::assertStringContainsString('media/js/front.js', $readme);
    }

    /**
     * Возвращает PHP-файлы проекта без vendor/node_modules.
     */
    private function phpFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());

            if (!str_ends_with($path, '.php')) {
                continue;
            }

            if (str_contains($path, '/vendor/') || str_contains($path, '/node_modules/')) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }
}
