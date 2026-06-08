<?php

declare(strict_types=1);

namespace Tests\Php\Contract;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
final class ManifestTest extends TestCase
{
    private string $root;
    private \SimpleXMLElement $manifest;

    /**
     * Загружает manifest как contract-документ без Joomla runtime.
     */
    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 3);
        $this->manifest = new \SimpleXMLElement(file_get_contents($this->root . '/mod_ishop_compare.xml'));
    }

    /**
     * Проверяет базовую XML-валидность manifest.
     */
    public function testManifestXmlIsValid(): void
    {
        self::assertSame('extension', $this->manifest->getName());
    }

    /**
     * Проверяет атрибуты site module extension.
     */
    public function testJoomlaExtensionAttributes(): void
    {
        self::assertSame('module', (string) $this->manifest['type']);
        self::assertSame('site', (string) $this->manifest['client']);
        self::assertSame('upgrade', (string) $this->manifest['method']);
    }

    /**
     * Проверяет имя и namespace модуля.
     */
    public function testModuleNameAndNamespace(): void
    {
        self::assertSame('mod_ishop_compare', (string) $this->manifest->name);
        self::assertSame('Ilange\Module\Ishopcompare', (string) $this->manifest->namespace);
        self::assertSame('src', (string) $this->manifest->namespace['path']);
    }

    /**
     * Проверяет существование файлов и папок из manifest.
     */
    public function testManifestDeclaredFilesExist(): void
    {
        self::assertFileExists($this->root . '/script.php');

        foreach ($this->manifest->files->folder as $folder) {
            self::assertDirectoryExists($this->root . '/' . (string) $folder);
        }

        foreach ($this->manifest->languages->language as $language) {
            self::assertFileExists($this->root . '/' . (string) $language);
        }

        foreach ($this->manifest->media->children() as $mediaItem) {
            $path = $this->root . '/media/' . (string) $mediaItem;

            if ($mediaItem->getName() === 'folder') {
                self::assertDirectoryExists($path);
            } else {
                self::assertFileExists($path);
            }
        }
    }

    /**
     * Проверяет параметры manifest и их базовые атрибуты.
     */
    public function testManifestParameters(): void
    {
        $fields = $this->fieldsByName();
        $expected = [
            'show_text' => ['type' => 'radio', 'default' => '1', 'label' => 'MOD_ISHOP_COMPARE_XML_SHOW_TEXT'],
            'show_count' => ['type' => 'radio', 'default' => '1', 'label' => 'MOD_ISHOP_COMPARE_XML_SHOW_COUNT'],
            'show_btn_clear' => ['type' => 'radio', 'default' => '1', 'label' => 'MOD_ISHOP_COMPARE_XML_SHOW_BTN_CLEAR'],
            'use_css' => ['type' => 'radio', 'default' => '1', 'label' => 'MOD_ISHOP_COMPARE_XML_USE_CSS', 'description' => 'MOD_ISHOP_COMPARE_XML_USE_CSS_DESC'],
            'use_js' => ['type' => 'radio', 'default' => '1', 'label' => 'MOD_ISHOP_COMPARE_XML_USE_JS', 'description' => 'MOD_ISHOP_COMPARE_XML_USE_JS_DESC'],
            'layout' => ['type' => 'modulelayout', 'label' => 'JFIELD_ALT_LAYOUT_LABEL'],
        ];

        foreach ($expected as $name => $attributes) {
            self::assertArrayHasKey($name, $fields);

            foreach ($attributes as $attribute => $value) {
                self::assertSame($value, (string) $fields[$name][$attribute]);
            }
        }
    }

    /**
     * Проверяет radio/switcher options для boolean-параметров.
     */
    public function testBooleanFieldsUseSwitcherOptions(): void
    {
        foreach (['show_text', 'show_count', 'show_btn_clear', 'use_css', 'use_js'] as $name) {
            $field = $this->fieldsByName()[$name];
            self::assertSame('joomla.form.field.radio.switcher', (string) $field['layout']);

            $options = [];
            foreach ($field->option as $option) {
                $options[(string) $option['value']] = (string) $option;
            }

            self::assertSame(['0' => 'JNO', '1' => 'JYES'], $options);
        }
    }

    /**
     * Проверяет синхронизацию версий package, XML и asset manifest.
     */
    public function testVersionsAreSynchronized(): void
    {
        $package = json_decode(file_get_contents($this->root . '/package.json'), true, 512, JSON_THROW_ON_ERROR);
        $assets = json_decode(file_get_contents($this->root . '/media/joomla.asset.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($package['version'], (string) $this->manifest->version);
        self::assertSame($package['version'], $assets['version']);

        foreach ($assets['assets'] as $asset) {
            self::assertSame($package['version'], $asset['version']);
        }
    }

    /**
     * Проверяет scriptfile и класс installer script.
     */
    public function testScriptfileContract(): void
    {
        self::assertSame('script.php', (string) $this->manifest->scriptfile);
        self::assertFileExists($this->root . '/script.php');
        self::assertStringContainsString('class Mod_Ishop_compareInstallerScript', file_get_contents($this->root . '/script.php'));
    }

    /**
     * Проверяет update server и changelog URL.
     */
    public function testUpdateServerContract(): void
    {
        self::assertNotEmpty((string) $this->manifest->changelogurl);
        self::assertSame('extension', (string) $this->manifest->updateservers->server['type']);
        self::assertSame('mod_ishop_compare', (string) $this->manifest->updateservers->server['name']);
        self::assertNotEmpty((string) $this->manifest->updateservers->server);
    }

    /**
     * Возвращает поля manifest по имени.
     */
    private function fieldsByName(): array
    {
        $fields = [];

        foreach ($this->manifest->xpath('//field') as $field) {
            $fields[(string) $field['name']] = $field;
        }

        return $fields;
    }
}
