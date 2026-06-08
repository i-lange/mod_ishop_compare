# AGENTS.md

## Назначение проекта

`mod_ishop_compare` - устанавливаемое site-расширение, модуль для CMS Joomla 6. Модуль отображает текущее состояние списка сравнения товаров: ссылку на страницу сравнения, количество товаров в сравнении и, при включенном параметре, кнопку очистки.

## Связанные проекты и расширения
Данный модуль разрабатывается для интернет-магазина на Joomla.
Собранный production-ready проект это magazin-gefest-new.local, он доступен:
- в окружении Windows путь к директории проекта: "c:\OSPanel\home\magazin-gefest-new.local\"
- в окружении WSL путь к директории проекта: "mnt/c/OSPanel/home/magazin-gefest-new.local"
- на локальном сервере (сервер всегда запущен по-умолчанию) панель администратора доступна по адресу: https://magazin-gefest-new.local/administrator/
- на локальном сервере (сервер всегда запущен по-умолчанию) фронтенд сайта доступен по адресу: https://magazin-gefest-new.local

Расширения, которые работают вместе в рамках magazin-gefest-new.local:
- `com_ishop` (Windows: `c:\OSPanel\home\com_ishop\`) - основной компонент интернет-магазина. `mod_ishop_compare` напрямую зависит от него: в `src/Dispatcher/Dispatcher.php` модуль загружает компонент через `bootComponent('com_ishop')`, создает site-модель `Compare` и получает количество товаров через `getCount()`. В `tmpl/default.php` используется `Ilange\Component\Ishop\Site\Helper\RouteHelper::getCompareRoute()` для ссылки на страницу сравнения. При изменении модели сравнения, маршрутов, namespace или публичных helper API в `com_ishop` нужно синхронно проверять этот модуль.
- `com_ishopintegro` (Windows: `c:\OSPanel\home\com_ishopintegro\`) - компонент интеграций интернет-магазина со сторонними сервисами и обменом данными. Может влиять на состав и свойства товаров, которые затем используются в `com_ishop` и отображаются в сравнении.
- `mod_ishop_cart` (Windows: `c:\OSPanel\home\mod_ishop_cart\`) - модуль корзины. Обычно размещается рядом с модулем сравнения в интерфейсе сайта; при изменениях в общей навигации, иконках, счетчиках, AJAX-поведении и CSS-селекторов учитывайте визуальную и поведенческую согласованность этих модулей.
- `mod_ishop_compare` (Windows: `c:\OSPanel\home\mod_ishop_compare\`) - текущий модуль состояния списка сравнения товаров.
- `mod_ishop_filter` (Windows: `c:\OSPanel\home\mod_ishop_filter\`) - модуль фильтрации товаров в категории. Фильтр, карточки товаров и действия "сравнить" должны оставаться согласованными по параметрам товаров и пользовательскому состоянию.
- `mod_ishop_zone` (Windows: `c:\OSPanel\home\mod_ishop_zone\`) - модуль выбора зоны доставки/местоположения. Зона может влиять на доступность, цены или свойства товаров в магазине, что важно для страниц каталога, карточки и сравнения.
- `plg_ishopfinder` (Windows: `c:\OSPanel\home\plg_ishopfinder\`) - плагин индексации товаров в штатный поиск Joomla. Учитывайте его при изменении структуры данных товаров и ссылок.
- `plg_ishopintegrocron` (Windows: `c:\OSPanel\home\plg_ishopintegrocron\`) - плагин запуска методов `com_ishopintegro` из планировщика задач Joomla.
- `tpl_itheme` (Windows: `c:\OSPanel\home\tpl_itheme\`) - шаблон всей клиентской части сайта. Разметка и классы модуля должны быть совместимы с шаблоном, его Bootstrap 5.3-подходами, позициями модулей и общими стилями.
- `plg_ithemecsscompiler` (Windows: `c:\OSPanel\home\plg_ithemecsscompiler\`) - плагин, который добавляет в `tpl_itheme` возможность компилировать стили из административной панели Joomla.

При внесении изменений в проект нужно держать во внимании этот контекст. Все расширения дополняют друг друга, а `mod_ishop_compare` особенно чувствителен к изменениям в `com_ishop`, `tpl_itheme` и соседних пользовательских модулях (`mod_ishop_cart`, `mod_ishop_filter`, `mod_ishop_zone`).

## Официальный контекст Joomla 6

При изменениях сверяйтесь с официальной документацией Joomla, особенно:

- Getting Started: https://manual.joomla.org/docs/get-started/
- Technical Requirements: https://manual.joomla.org/docs/get-started/technical-requirements/
- Module Development Tutorial: https://manual.joomla.org/docs/building-extensions/modules/module-development-tutorial/
- Web Asset Manager: https://manual.joomla.org/docs/general-concepts/web-asset-manager/


## Стек и окружение

- Joomla CMS 6.x, `method="upgrade"`.
- PHP 8.3+; для Joomla 6.x ориентируйтесь на актуальные требования официальной документации.
- Для вывода html по-умолчанию используются подходы Bootstrap 5.3.

## Команды

- `pnpm install` - установить JS-зависимости по `pnpm-lock.yaml`.
- `pnpm build` - полная сборка CSS и JS через `build.mjs`.
- `pnpm build:css` - собрать `media/css/*.css`, `*.min.css`, `*.min.css.gz`.
- `pnpm build:js` - собрать `media/js/*.min.js`, `*.min.js.gz`.
- `pnpm watch:js` - наблюдать `media/js/*.min.js`, `*.min.js.gz`.
- `pnpm watch:css` - наблюдать `media/css/*.css`, `*.min.css`, `*.min.css.gz`.
- `pnpm test` - сейчас заглушка `No automated tests yet`.
- `pnpm zip` - `pnpm build` и создание установочного архива `mod_ishop_compare-{version}.zip`.

## Правила внесения изменений

- Сначала меняйте исходники: SCSS в `media/scss`, обычные JS entrypoints в `media/js`, PHP-код в `src`, `services`, `tmpl`, `script.php` и манифесте `mod_ishop_compare.xml`. Не правьте вручную `.min.css`, `.min.js`, `.gz`, если изменение должно генерироваться сборкой.
- После изменения SCSS/JS запускайте соответствующую сборку и включайте сгенерированные assets, если проект ожидает готовый installable module.
- `vite.config.css.mts` использует `emptyOutDir: true` для `media/css`; не держите там ручные файлы, которые не должны удаляться сборкой.
- В PHP-файлах сохраняйте `defined('_JEXEC') or die;`, namespaced Joomla API (`Factory`, `HTMLHelper`, `Text`, `LayoutHelper`, `Route`) и существующий стиль модуля.
- Экранируйте вывод: `$this->escape()`, `htmlspecialchars()`, `HTMLHelper::cleanImageURL()`, `Text::_()` и явные приведения типов там, где данные приходят из params/input/model.
- Формы должны содержать Joomla CSRF token через `HTMLHelper::_('form.token')`; новые POST/AJAX сценарии должны учитывать Joomla token и права доступа.
- Новые assets регистрируйте в `joomla.asset.json` с понятными именами, `type`, `uri`, `attributes` и `dependencies`.
- Если добавляете новый JS entrypoint, обновите `JS_ENTRY_FILES` в `vite.config.js.mts` и asset declaration в `joomla.asset.json`.
- Если добавляете новый SCSS entrypoint, обновите `SCSS_ENTRIES` в `vite.config.css.mts` и asset declaration в `joomla.asset.json`.
- При изменении версии расширения обновляйте ее синхронно в трех местах: `package.json`, `<version>` в `mod_ishop_compare.xml` и `version` в `media/joomla.asset.json` (включая версии конкретных asset-записей). Это нужно, чтобы имя архива `mod_ishop_compare-{version}.zip`, манифест Joomla и asset-декларации не расходились.
- Если изменение затрагивает зависимость от `com_ishop`, проверяйте совместимость с `Compare` site-моделью, `RouteHelper::getCompareRoute()` и пользовательским состоянием списка сравнения.
- Для Bootstrap-разметки используйте классы и data-атрибуты Bootstrap 5.3 (`data-bs-*`), а не устаревшие Bootstrap 4 подходы.
- Поддерживайте accessibility: `aria-label`, `visually-hidden`, корректные `button`/`a`, возврат фокуса в offcanvas/modal и видимые состояния focus.
- При добавлении языковых ключей обновляйте обе локали `en-GB` и `ru-RU`.
- Не редактируйте `node_modules`.

## Проверка перед сдачей

Минимальный набор:

- `pnpm build`
- `pnpm test`
- `pnpm zip`

Если Node.js недоступен, явно сообщите, что команды не запускались из-за окружения. 
Для функциональной проверки установите zip в Joomla 6 по адресу https://magazin-gefest-new.local/administrator/index.php?option=com_installer&view=install и проверьте как минимум главную, категорию, карточку товара, корзину, checkout, поиск, логин, 403/404 и offline page.

## Ограничения и известные состояния

- Это не полный сайт Joomla, а только модуль, устанавливаемый как расширение. Корневые PHP-файлы нельзя полноценно запускать вне Joomla application context.
- Автоматических тестов пока нет; `pnpm test` является заглушкой.
