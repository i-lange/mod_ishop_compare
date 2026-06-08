<?php

declare(strict_types=1);

namespace Tests\Php\Contract;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Php\Support\IniFileReader;

#[Group('contract')]
final class LanguageFilesTest extends TestCase
{
    private string $root;

    /**
     * Сохраняет корень проекта для чтения language-файлов.
     */
    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 3);
    }

    /**
     * Проверяет frontend-ключи layout в обеих локалях.
     */
    public function testFrontendKeysExist(): void
    {
        foreach (['en-GB', 'ru-RU'] as $locale) {
            $keys = $this->keys($locale, 'ini');

            foreach (['MOD_ISHOP_COMPARE_TEXT', 'MOD_ISHOP_COMPARE_COUNT', 'MOD_ISHOP_COMPARE_CLEAR'] as $key) {
                self::assertContains($key, $keys);
            }
        }
    }

    /**
     * Проверяет manifest language keys в ini или sys.ini обеих локалей.
     */
    public function testManifestKeysExist(): void
    {
        $manifest = new \SimpleXMLElement(file_get_contents($this->root . '/mod_ishop_compare.xml'));
        $required = [];

        foreach ($manifest->xpath('//@label | //@description') as $attribute) {
            $key = (string) $attribute;

            if (str_starts_with($key, 'MOD_ISHOP_COMPARE_')) {
                $required[] = $key;
            }
        }

        foreach (['en-GB', 'ru-RU'] as $locale) {
            $keys = array_merge($this->keys($locale, 'ini'), $this->keys($locale, 'sys.ini'));

            foreach (array_unique($required) as $key) {
                self::assertContains($key, $keys, $locale . ' missing ' . $key);
            }
        }
    }

    /**
     * Проверяет installer language keys в sys.ini обеих локалей.
     */
    public function testInstallerKeysExist(): void
    {
        $required = [
            'MOD_ISHOP_COMPARE_XML_UNINSTALL_OK',
            'MOD_ISHOP_COMPARE_VERSION',
            'MOD_ISHOP_COMPARE_AUTHOR',
            'MOD_ISHOP_COMPARE_DONATE',
            'MOD_ISHOP_COMPARE_DONATE_URL',
            'MOD_ISHOP_COMPARE_DONATE_BTN',
        ];

        foreach (['en-GB', 'ru-RU'] as $locale) {
            $keys = $this->keys($locale, 'sys.ini');

            foreach ($required as $key) {
                self::assertContains($key, $keys, $locale . ' missing ' . $key);
            }
        }
    }

    /**
     * Проверяет отсутствие дублей ключей внутри каждого language-файла.
     */
    public function testNoDuplicateKeysInsideLanguageFiles(): void
    {
        foreach (['en-GB', 'ru-RU'] as $locale) {
            foreach (['ini', 'sys.ini'] as $suffix) {
                $file = $this->languageFile($locale, $suffix);
                $data = IniFileReader::read($file);

                self::assertSame([], $data['duplicates'], $file . ' has duplicates');
            }
        }
    }

    /**
     * Проверяет согласованность наборов ключей en-GB и ru-RU.
     */
    public function testLanguageKeySetsAreSynchronized(): void
    {
        foreach (['ini', 'sys.ini'] as $suffix) {
            $en = $this->keys('en-GB', $suffix);
            $ru = $this->keys('ru-RU', $suffix);
            sort($en);
            sort($ru);

            self::assertSame($en, $ru, $suffix . ' keys are not synchronized');
        }
    }

    /**
     * Возвращает ключи конкретного language-файла.
     */
    private function keys(string $locale, string $suffix): array
    {
        return IniFileReader::read($this->languageFile($locale, $suffix))['keys'];
    }

    /**
     * Строит путь к language-файлу.
     */
    private function languageFile(string $locale, string $suffix): string
    {
        return $this->root . '/language/' . $locale . '/mod_ishop_compare.' . $suffix;
    }
}
