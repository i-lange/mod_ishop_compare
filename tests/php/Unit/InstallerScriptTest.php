<?php

declare(strict_types=1);

namespace Tests\Php\Unit;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Php\Support\FakeApp;
use Tests\Php\Support\FakeCompareModel;
use Tests\Php\Support\FakeComponent;
use Tests\Php\Support\FakeDocument;
use Tests\Php\Support\FakeMVCFactory;
use Tests\Php\Support\FakeWebAssetManager;

#[Group('unit')]
final class InstallerScriptTest extends TestCase
{
    private FakeApp $app;

    /**
     * Настраивает Factory и parent installer stub перед каждым тестом.
     */
    protected function setUp(): void
    {
        $this->app = new FakeApp(
            new FakeDocument(new FakeWebAssetManager()),
            new FakeComponent(new FakeMVCFactory(new FakeCompareModel(0)))
        );
        Factory::setApplication($this->app);
        InstallerScript::reset();
        Text::reset();
        require_once dirname(__DIR__, 3) . '/script.php';
    }

    /**
     * Проверяет доступность класса installer script.
     */
    public function testInstallerScriptClassIsAvailable(): void
    {
        self::assertTrue(class_exists(\Mod_Ishop_compareInstallerScript::class));
    }

    /**
     * Проверяет, что constructor сохраняет app из Factory.
     */
    public function testConstructorStoresApplication(): void
    {
        $script = new \Mod_Ishop_compareInstallerScript();
        $property = new \ReflectionProperty($script, 'app');

        self::assertSame($this->app, $property->getValue($script));
    }

    /**
     * Проверяет preflight при неуспешном parent preflight.
     */
    public function testPreflightReturnsFalseWhenParentFails(): void
    {
        InstallerScript::$preflightResult = false;
        $script = new \Mod_Ishop_compareInstallerScript();

        self::assertFalse($script->preflight('install', new InstallerAdapter()));
    }

    /**
     * Проверяет preflight при успешном parent preflight.
     */
    public function testPreflightReturnsTrueWhenParentSucceeds(): void
    {
        InstallerScript::$preflightResult = true;
        $script = new \Mod_Ishop_compareInstallerScript();

        self::assertTrue($script->preflight('install', new InstallerAdapter()));
    }

    /**
     * Проверяет update output и вызов removeFiles().
     */
    public function testPostflightUpdateOutputsReleaseInformation(): void
    {
        Text::setTranslations([
            'MOD_ISHOP_COMPARE_DONATE_URL' => 'https://ilange.ru/avtor/podderzhat',
            'MOD_ISHOP_COMPARE_DONATE_BTN' => 'Support the author',
        ]);
        $script = new \Mod_Ishop_compareInstallerScript();
        $manifest = new \SimpleXMLElement(
            '<extension><name>mod_ishop_compare</name><version>1.0.0</version><author>Pavel Lange</author></extension>'
        );

        ob_start();
        $result = $script->postflight('update', new InstallerAdapter($manifest));
        $html = (string) ob_get_clean();

        self::assertTrue($result);
        self::assertSame(1, InstallerScript::$removeFilesCalls);
        self::assertStringContainsString('mod_ishop_compare', $html);
        self::assertStringContainsString('1.0.0', $html);
        self::assertStringContainsString('Pavel Lange', $html);
        self::assertStringContainsString('https://ilange.ru', $html);
        self::assertStringContainsString('https://github.com/i-lange/mod_ishop_compare', $html);
        self::assertStringContainsString('https://ilange.ru/avtor/podderzhat', $html);
        self::assertStringContainsString('Support the author', $html);
    }

    /**
     * Проверяет uninstall warning message.
     */
    public function testPostflightUninstallEnqueuesWarning(): void
    {
        $script = new \Mod_Ishop_compareInstallerScript();

        self::assertTrue($script->postflight('uninstall', new InstallerAdapter()));
        self::assertSame(1, InstallerScript::$removeFilesCalls);
        self::assertSame([['MOD_ISHOP_COMPARE_XML_UNINSTALL_OK', 'warning']], $this->app->messages);
    }

    /**
     * Проверяет install и discover_install без update HTML и uninstall warning.
     */
    public function testPostflightOtherTypesOnlyRemoveFiles(): void
    {
        foreach (['install', 'discover_install'] as $type) {
            InstallerScript::reset();
            $script = new \Mod_Ishop_compareInstallerScript();

            ob_start();
            self::assertTrue($script->postflight($type, new InstallerAdapter()));
            $html = (string) ob_get_clean();

            self::assertSame(1, InstallerScript::$removeFilesCalls);
            self::assertSame('', $html);
            self::assertSame([], $this->app->messages);
        }
    }

    /**
     * Проверяет минимальные версии для Joomla 6 и PHP 8.3+.
     */
    public function testMinimumVersionsMatchProjectPolicy(): void
    {
        $script = new \Mod_Ishop_compareInstallerScript();

        self::assertSame('8.3', (new \ReflectionProperty($script, 'minimumPhp'))->getValue($script));
        self::assertSame('6.0.0', (new \ReflectionProperty($script, 'minimumJoomla'))->getValue($script));
    }

    /**
     * Проверяет экранирование HTML из manifest fields.
     */
    public function testUpdateOutputEscapesManifestValues(): void
    {
        $script = new \Mod_Ishop_compareInstallerScript();
        $manifest = new \SimpleXMLElement(
            '<extension><name>mod_ishop_compare&lt;script&gt;</name><version>1.0.0&lt;b&gt;</version><author>Pavel &lt;img /&gt;</author></extension>'
        );

        ob_start();
        $script->postflight('update', new InstallerAdapter($manifest));
        $html = (string) ob_get_clean();

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringNotContainsString('<b>', $html);
        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('mod_ishop_compare&lt;script&gt;', $html);
    }
}
