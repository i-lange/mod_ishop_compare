<?php

declare(strict_types=1);

namespace Tests\Php\Contract;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
final class AssetManifestTest extends TestCase
{
    private string $root;
    private array $manifest;

    /**
     * Загружает Joomla asset manifest как JSON contract.
     */
    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 3);
        $this->manifest = json_decode(
            file_get_contents($this->root . '/media/joomla.asset.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * Проверяет верхнеуровневые поля asset manifest.
     */
    public function testTopLevelFields(): void
    {
        foreach (['$schema', 'name', 'version', 'description', 'license', 'assets'] as $field) {
            self::assertArrayHasKey($field, $this->manifest);
        }

        self::assertSame('mod_ishop_compare', $this->manifest['name']);
    }

    /**
     * Проверяет style asset.
     */
    public function testStyleAsset(): void
    {
        $asset = $this->asset('style');
        $package = json_decode(file_get_contents($this->root . '/package.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('mod_ishop_compare.front', $asset['name']);
        self::assertSame('mod_ishop_compare/front.css', $asset['uri']);
        self::assertSame($package['version'], $asset['version']);
    }

    /**
     * Проверяет script asset, dependencies и attributes.
     */
    public function testScriptAsset(): void
    {
        $asset = $this->asset('script');
        $package = json_decode(file_get_contents($this->root . '/package.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('mod_ishop_compare.front', $asset['name']);
        self::assertSame('mod_ishop_compare/front.js', $asset['uri']);
        self::assertSame($package['version'], $asset['version']);
        self::assertContains('core', $asset['dependencies']);
        self::assertTrue($asset['attributes']['defer']);
    }

    /**
     * Проверяет соответствие asset URI реальным source-файлам.
     */
    public function testAssetUrisPointToRealFiles(): void
    {
        self::assertFileExists($this->root . '/media/css/front.css');
        self::assertFileExists($this->root . '/media/js/front.js');
    }

    /**
     * Проверяет, что WAM declaration не ссылается на minified/gzip generated assets.
     */
    public function testAssetUrisDoNotPointToGeneratedFiles(): void
    {
        foreach ($this->manifest['assets'] as $asset) {
            self::assertStringNotContainsString('.min.', $asset['uri']);
            self::assertFalse(str_ends_with($asset['uri'], '.gz'));
        }
    }

    /**
     * Находит asset по типу.
     */
    private function asset(string $type): array
    {
        foreach ($this->manifest['assets'] as $asset) {
            if ($asset['type'] === $type) {
                return $asset;
            }
        }

        self::fail('Asset type not found: ' . $type);
    }
}
