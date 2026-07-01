# AGENTS.md

## Контекст проекта

`mod_ishop_compare` - устанавливаемый site-модуль Joomla 6 для магазина на `com_ishop`. Он показывает ссылку на сравнение товаров, текущий счетчик и, если включен параметр, кнопку очистки списка.

Рабочий сайт для ручной проверки: `C:\OSPanel\home\magazin-gefest-new.local`, фронтенд `https://magazin-gefest-new.local`, админка `https://magazin-gefest-new.local/administrator/`.

Критичные соседние расширения:
- `com_ishop` (`C:\OSPanel\home\com_ishop`) - основная зависимость. `src/Dispatcher/Dispatcher.php` вызывает `bootComponent('com_ishop')`, создает site-модель `Compare` и читает `getCount()`. `tmpl/default.php` использует `Ilange\Component\Ishop\Site\Helper\RouteHelper::getCompareRoute()`.
- `tpl_itheme` (`C:\OSPanel\home\tpl_itheme`) - шаблон сайта, Bootstrap 5.3-подходы, позиции модулей и общие стили.
- `mod_ishop_cart`, `mod_ishop_filter`, `mod_ishop_zone` - соседние пользовательские модули; держите согласованными навигацию, счетчики, AJAX, CSS hooks и пользовательское состояние.
- `com_ishopintegro`, `plg_ishopfinder`, `plg_ishopintegrocron`, `plg_ithemecsscompiler` - связанные интеграции, поиск, cron и компиляция стилей.

## Документация

Для вопросов по библиотекам, фреймворкам, SDK, API, CLI и облачным сервисам используйте Context7 MCP: сначала `resolve-library-id`, затем `query-docs`. Для Joomla 6 дополнительно сверяйтесь с официальной документацией:
- https://manual.joomla.org/docs/get-started/
- https://manual.joomla.org/docs/get-started/technical-requirements/
- https://manual.joomla.org/docs/building-extensions/modules/module-development-tutorial/
- https://manual.joomla.org/docs/general-concepts/web-asset-manager/

## Стек

- Joomla CMS 6.x, extension manifest `method="upgrade"`.
- PHP 8.3+; на Windows для Composer/PHPUnit используйте `C:\OSPanel\modules\PHP-8.3\PHP\php.exe`, потому что глобальный `php` может быть без нужных расширений.
- Node.js `>=24.0.0`, npm `>=11.8.0`, pnpm `>=10.3.0`.
- Vite 8, Vitest 4 в `happy-dom`, PHPUnit 11.5 из Composer dev-зависимостей.
- HTML по умолчанию ориентирован на Bootstrap 5.3.

## Команды

```powershell
pnpm install
C:\OSPanel\modules\PHP-8.3\PHP\php.exe C:\OSPanel\data\PHP-8.3\default\composer\composer.phar install

pnpm build          # CSS + JS через build.mjs
pnpm build:css      # media/css/*.css, *.min.css, *.min.css.gz
pnpm build:js       # media/js/*.min.js, *.min.js.gz
pnpm watch:css
pnpm watch:js

pnpm test:php       # PHPUnit unit/layout/contract
pnpm test:js        # Vitest tests/js/front.test.js
pnpm test:build     # build + build-config tests
pnpm test:zip       # zip + packaging tests
pnpm test           # php + js + build + zip
pnpm test:coverage  # PHP Clover + JS coverage
pnpm zip            # build + build/mod_ishop_compare-{version}.zip
```

## Тесты

Тесты автономны: не поднимают Joomla CMS, `magazin-gefest-new.local`, базу данных, настоящий Web Asset Manager или реальный `com_ishop`; используются stubs/test doubles.

Уровни:
- `unit` - `src/Dispatcher/Dispatcher.php`, `services/provider.php`, `script.php`.
- `layout` - `tmpl/default.php` через автономный renderer.
- `contract` - manifest, asset manifest, language-файлы, `_JEXEC` guards, syntax, namespace.
- `build` - реальные CSS/JS artifacts после сборки.
- `packaging` - installable zip через чтение central directory без распаковки.

Ключевые файлы: `phpunit.xml`, `vitest.config.mts`, `tests/php/bootstrap.php`, `tests/php/stubs/JoomlaStubs.php`, `tests/php/Support/TestDoubles.php`, `tests/tools/run-phpunit.mjs`, `tests/tools/check-php-coverage.mjs`.

## Правила изменений

- Сначала меняйте исходники: PHP в `src`, `services`, `tmpl`, `script.php`; SCSS в `media/scss`; ручной JS entrypoint в `media/js/front.js`; манифест в `mod_ishop_compare.xml`.
- Не правьте вручную generated assets: `media/css/*.css`, `*.min.css`, `*.gz`, `media/js/*.min.js`, `*.gz`, если изменение должно идти через сборку. После изменений SCSS/JS запускайте соответствующий build и включайте generated artifacts.
- `vite.config.css.mts` очищает `media/css` (`emptyOutDir: true`); не храните там ручные файлы. JS entrypoints перечислены в `JS_ENTRY_FILES`, SCSS entrypoints - в `SCSS_ENTRIES`.
- Новые assets регистрируйте в `media/joomla.asset.json` с корректными `type`, `uri`, `dependencies`, `attributes` и версиями.
- Версию расширения меняйте синхронно в `package.json`, `<version>` в `mod_ishop_compare.xml`, `media/joomla.asset.json` и версиях asset-записей.
- В PHP сохраняйте `defined('_JEXEC') or die;`, namespaced Joomla API и текущий стиль модуля.
- Экранируйте вывод через `$this->escape()`, `htmlspecialchars()`, `HTMLHelper::cleanImageURL()`, `Text::_()` и явные приведения типов для данных из params/input/model.
- Формы/POST/AJAX должны учитывать Joomla CSRF token через `HTMLHelper::_('form.token')` или актуальный JS token и права доступа.
- Bootstrap-разметка должна использовать Bootstrap 5.3 и `data-bs-*`, не Bootstrap 4.
- Поддерживайте accessibility: корректные `button`/`a`, `aria-label`, `visually-hidden`, видимые focus states, возврат фокуса в offcanvas/modal.
- Новые языковые ключи добавляйте в обе локали: `language/en-GB` и `language/ru-RU`.
- Комментарии добавляйте на русском только для неочевидных контрактов, stubs, тестовой логики или поведения.
- Не редактируйте `node_modules` и `vendor`.

## Стабильные контракты

- `media/js/front.js` экспортирует `FRONT_ENTRY_MARKER`, `initIshopCompare(root = document, JoomlaApi = window.Joomla)` и публикует API в `globalThis.IshopCompare`. Импорт entrypoint не должен сам отправлять сетевые запросы или менять DOM.
- AJAX очистки сравнения зафиксирован тестами: `POST ?option=com_ajax&module=ishop_compare&method=clear&format=json`, JSON payload, headers `Cache-Control` и `Content-Type`.
- Layout hooks должны сохраняться: `data-ishop-compare`, `data-ishop-compare-id`, `data-ishop-compare-clear`, `data-ishop-compare-target`.
- При изменениях зависимости от `com_ishop` проверяйте совместимость с site-моделью `Compare`, `RouteHelper::getCompareRoute()` и пользовательским состоянием сравнения.

## Проверка перед сдачей

Минимально для кода:

```powershell
pnpm test
pnpm test:coverage
pnpm build
pnpm zip
```

Если менялись только документы, достаточно сверить инструкции с `package.json`, `composer.json`, `phpunit.xml`, `vitest.config.mts` и `README.md`.

Clean-checkout проверка: установить pnpm-зависимости, установить Composer dev-зависимости через OSPanel PHP 8.3, затем выполнить `pnpm test`. Она не должна требовать сайта, БД, Joomla CMS или реального `com_ishop`.

Если Node.js, pnpm, PHP 8.3 или Composer из OSPanel недоступны, явно укажите, какие команды не запускались. Установленный zip на `magazin-gefest-new.local` проверяется вручную перед релизом: главная, категория, карточка товара, корзина, checkout, поиск, логин, 403/404 и offline page.

## Ограничения

Это не полный сайт Joomla, а устанавливаемый модуль. Корневые PHP-файлы нельзя полноценно запускать вне Joomla application context. Автотесты намеренно не проверяют реальную установку расширения, настоящую БД, CMS Web Asset Manager и реальный `com_ishop`.
