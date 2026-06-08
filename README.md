# Модуль iShop_compare
## _Расширение для CMS_ ![Расширение для Joomla!](https://cdn.joomla.org/images/Joomla_logo.png) _Joomla! v6_
[![N|iLange.ru](https://lange.ru/templates/ilange/images/logo.svg)](https://lange.ru)

## Модуль отображения состояния списка сравнения
- Отображение панели списка сравнения
- Отображение количества товаров в списке

## Автономные тесты

Автоматические тесты не поднимают реальную Joomla CMS и не требуют доступного сайта `https://magazin-gefest-new.local`. В тестах не запускаются `com_installer`, настоящий Joomla application context, база данных, CMS Web Asset Manager и реальный `com_ishop`; для них используются stubs и test doubles.

Покрываются уровни:
- `unit` - dispatcher, service provider и installer script;
- `layout` - `tmpl/default.php` через автономный renderer;
- `contract` - manifest, language-файлы, asset manifest, guards, syntax и namespace;
- `build` - реальные CSS/JS artifacts после сборки;
- `packaging` - installable zip через чтение central directory без распаковки.

Команды:
```sh
pnpm install
C:\OSPanel\modules\PHP-8.3\PHP\php.exe C:\OSPanel\data\PHP-8.3\default\composer\composer.phar install
pnpm test:php
pnpm test:js
pnpm test:build
pnpm test:zip
pnpm test
pnpm test:coverage
pnpm build
pnpm zip
```

Обычная команда `pnpm test` последовательно запускает PHP, JS, build и zip-проверки. Coverage строится отдельно: PHP Clover пишется в `build/coverage/php-clover.xml`, JS coverage - в `coverage/js`.

Source-политика assets:
- исходник CSS - `media/scss/front.scss`;
- исходник JS - `media/js/front.js`;
- `media/css/*.css`, `media/css/*.min.css`, `media/css/*.gz`, `media/js/*.min.js` и `media/js/*.gz` проверяются после build и не должны правиться вручную без изменения source-файлов.

## Установка
- Скачать архив из репозитория
- В панели администратора открыть Установка -> Расширения
```sh
(https://ваш_сайт/administrator/index.php?option=com_installer&view=install)
```
- Загрузить архив с модулем и дождаться окончания установки
- **Подробнее в [документации](https://help.joomla.org/proxy?keyref=Help42:Extensions:_Install#Install_from_Web_Tab) Joomla!**

## Ручная релизная проверка Joomla

Проверка установленного zip на `https://magazin-gefest-new.local` остается ручной релизной проверкой и не входит в `pnpm test`. Перед релизом нужно установить архив через `https://magazin-gefest-new.local/administrator/index.php?option=com_installer&view=install` и проверить главную, категорию, карточку товара, корзину, checkout, поиск, логин, 403/404 и offline page.

## Лицензия

GNU General Public License версия 2 или более поздняя
