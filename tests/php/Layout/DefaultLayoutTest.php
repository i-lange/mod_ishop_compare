<?php

declare(strict_types=1);

namespace Tests\Php\Layout;

use Ilange\Component\Ishop\Site\Helper\RouteHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Php\Support\LayoutRenderer;

#[Group('layout')]
final class DefaultLayoutTest extends TestCase
{
    private LayoutRenderer $renderer;

    /**
     * Создает renderer, который выполняет tmpl/default.php без Joomla CMS.
     */
    protected function setUp(): void
    {
        $this->renderer = new LayoutRenderer();
    }

    /**
     * Проверяет подключение JS asset по параметру use_js.
     */
    public function testJavascriptAssetCanBeEnabledAndDisabled(): void
    {
        $enabled = $this->renderer->render(['use_js' => 1]);
        $disabled = $this->renderer->render(['use_js' => 0]);

        self::assertSame(['mod_ishop_compare.front'], $enabled->webAssetManager->scripts);
        self::assertSame([], $disabled->webAssetManager->scripts);
    }

    /**
     * Проверяет подключение CSS asset по параметру use_css.
     */
    public function testCssAssetCanBeEnabledAndDisabled(): void
    {
        $enabled = $this->renderer->render(['use_css' => 1]);
        $disabled = $this->renderer->render(['use_css' => 0]);

        self::assertSame(['mod_ishop_compare.front'], $enabled->webAssetManager->styles);
        self::assertSame([], $disabled->webAssetManager->styles);
    }

    /**
     * Проверяет вывод текста модуля.
     */
    public function testTextBlockCanBeShownAndHidden(): void
    {
        $shown = $this->renderer->render(['show_text' => 1])->html;
        $hidden = $this->renderer->render(['show_text' => 0])->html;

        self::assertStringContainsString('class="text"', $shown);
        self::assertStringContainsString('MOD_ISHOP_COMPARE_TEXT', $shown);
        self::assertStringNotContainsString('class="text"', $hidden);
    }

    /**
     * Проверяет вывод количества товаров.
     */
    public function testCountCanBeShownAndHidden(): void
    {
        $shown = $this->renderer->render(['show_count' => 1], 5)->html;
        $hidden = $this->renderer->render(['show_count' => 0], 5)->html;

        self::assertStringContainsString('<small class="count">5</small>', $shown);
        self::assertStringContainsString('MOD_ISHOP_COMPARE_COUNT', $shown);
        self::assertStringNotContainsString('class="count"', $hidden);
        self::assertStringNotContainsString('MOD_ISHOP_COMPARE_COUNT', $hidden);
    }

    /**
     * Проверяет вывод кнопки очистки.
     */
    public function testClearButtonCanBeShownAndHidden(): void
    {
        $shown = $this->renderer->render(['show_btn_clear' => 1])->html;
        $hidden = $this->renderer->render(['show_btn_clear' => 0])->html;

        self::assertStringContainsString('<button class="btn" type="button"', $shown);
        self::assertStringContainsString('MOD_ISHOP_COMPARE_CLEAR', $shown);
        self::assertStringNotContainsString('<button class="btn" type="button"', $hidden);
    }

    /**
     * Проверяет, что ссылка строится через RouteHelper и Route.
     */
    public function testCompareLinkUsesRouteHelperAndRoute(): void
    {
        $html = $this->renderer->render()->html;

        self::assertSame(['getCompareRoute'], RouteHelper::$calls);
        self::assertSame(['index.php?option=com_ishop&view=compare'], Route::$calls);
        self::assertStringContainsString('href="routed:index.php?option=com_ishop&amp;view=compare"', $html);
    }

    /**
     * Проверяет стабильные CSS/data hooks.
     */
    public function testStableCssAndDataHooksExist(): void
    {
        $html = $this->renderer->render(['module_id' => 77])->html;

        self::assertStringContainsString('class="mod_ishop_compare"', $html);
        self::assertStringContainsString('data-ishop-compare', $html);
        self::assertStringContainsString('data-ishop-compare-id="77"', $html);
    }

    /**
     * Проверяет безопасный вывод динамического count.
     */
    public function testDynamicCountIsSanitized(): void
    {
        $html = $this->renderer->render([], '10<script>alert(1)</script>')->html;

        self::assertStringContainsString('<small class="count">10</small>', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    /**
     * Проверяет доступное имя ссылки при отключенных видимых текстах.
     */
    public function testCompareLinkKeepsAccessibleNameWhenVisibleTextIsDisabled(): void
    {
        $html = $this->renderer->render(['show_text' => 0, 'show_count' => 0])->html;

        self::assertStringContainsString('aria-label="MOD_ISHOP_COMPARE_TEXT"', $html);
    }

    /**
     * Проверяет будущие JS hooks для кнопки очистки.
     */
    public function testClearButtonHasStableJavascriptHooks(): void
    {
        $html = $this->renderer->render(['module_id' => 42])->html;

        self::assertStringContainsString('data-ishop-compare-clear', $html);
        self::assertStringContainsString('data-ishop-compare-target="#mod-ishop-compare-42"', $html);
    }
}
