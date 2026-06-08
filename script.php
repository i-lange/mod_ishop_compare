<?php
/**
 * @package    mod_ishop_compare
 * @author     Pavel Lange <pavel@ilange.ru>
 * @link       https://github.com/i-lange/mod_ishop_compare
 * @copyright  (C) 2025 Pavel Lange <https://ilange.ru>
 * @license    GNU General Public License version 2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class Mod_Ishop_compareInstallerScript extends InstallerScript
{
    /**
     * Минимальная версия PHP, необходимая для установки модуля
     * @var string
     * @since 1.0.0
     */
    protected $minimumPhp = '8.3';

    /**
     * Минимальная версия Joomla, необходимая для установки модуля
     * @var string
     * @since 1.0.0
     */
    protected $minimumJoomla = '6.0.0';

    /**
     * Список файлов, которые необходимо удалить
     * @var array
     * @since 1.0.0
     */
    protected $deleteFiles = [];

    /**
     * Список папок, которые необходимо удалить
     * @var array
     * @since 1.0.0
     */
    protected $deleteFolders = [];

    /**
     * Объект приложения
     * @var object
     * @since 1.0.0
     */
    protected $app = null;

    /**
     * Конструктор
     * @throws Exception
     * @since 1.0.0
     */
    public function __construct()
    {
        // Получаем объект приложения
        $this->app = Factory::getApplication();
    }


    /**
     * Метод запускается непосредственно перед установкой/обновлением/удалением модуля
     * @param string $type Тип действия, которое выполняется (install|uninstall|discover_install|update)
     * @param InstallerAdapter $parent Класс, вызывающий этот метод.
     * @return boolean Возвращает True для продолжения, False для отмены установки/обновления/удаления
     * @throws Exception
     * @since 1.0.0
     */
    public function preflight($type, $parent): bool
    {
        if (!parent::preflight($type, $parent)) {
            return false;
        }

        return true;
    }

    /**
     * Метод запускается непосредственно после установки/обновления/удаления модуля
     * @param string $type Тип действия, которое выполняется (install|uninstall|discover_install|update)
     * @param InstallerAdapter $parent Класс, вызывающий этот метод.
     * @return boolean True при успешном выполнении
     * @throws Exception
     * @since 1.0.0
     */
    public function postflight(string $type, InstallerAdapter $parent): bool
    {
        // Удаляем файлы и папки, в которых больше нет необходимости
        $this->removeFiles();

        if ($type === 'update') {
            // Получаем данные из xml файла модуля
            $xml = $parent->getManifest();

            // Экранируем значения manifest перед сборкой HTML-сообщения установщика
            $extensionTitle = htmlspecialchars(Text::_('MOD_ISHOP_COMPARE'), ENT_QUOTES, 'UTF-8');
            $extensionName = htmlspecialchars((string) $xml->name, ENT_QUOTES, 'UTF-8');
            $extensionVersion = htmlspecialchars((string) $xml->version, ENT_QUOTES, 'UTF-8');
            $extensionAuthor = htmlspecialchars((string) $xml->author, ENT_QUOTES, 'UTF-8');
            $githubName = rawurlencode((string) $xml->name);
            $donateUrl = htmlspecialchars(Text::_('MOD_ISHOP_COMPARE_DONATE_URL'), ENT_QUOTES, 'UTF-8');
            $donateText = htmlspecialchars(Text::_('MOD_ISHOP_COMPARE_DONATE_BTN'), ENT_QUOTES, 'UTF-8');

            // Пишем сообщение со ссылками на сайт автора и на репозиторий
            $message[] = '<p class="fs-2 mb-2">' . $extensionTitle . ' [' . $extensionName . ']</p>';
            $message[] = '<ul>';
            $message[] = '<li>' . htmlspecialchars(Text::_('MOD_ISHOP_COMPARE_VERSION'), ENT_QUOTES, 'UTF-8') . ': ' . $extensionVersion . '</li>';
            $message[] = '<li>' . htmlspecialchars(Text::_('MOD_ISHOP_COMPARE_AUTHOR'), ENT_QUOTES, 'UTF-8') . ': ' . $extensionAuthor . '</li>';
            $message[] = '<li><a href="https://ilange.ru" target="_blank" rel="noopener noreferrer">https://ilange.ru</a></li>';
            $message[] = '<li><a href="https://github.com/i-lange/' . $githubName . '" target="_blank" rel="noopener noreferrer">GitHub</a></li>';
            $message[] = '</ul>';
            $message[] = '<p class="mb-2">' . htmlspecialchars(Text::_('MOD_ISHOP_COMPARE_DONATE'), ENT_QUOTES, 'UTF-8') . ': </p>';
            $message[] = '<a href="' . $donateUrl
                . '" target="_blank" rel="noopener noreferrer" class="btn btn-primary">' . $donateText . '</a>';
            $msgStr = implode('', $message);

            // Показываем сообщение
            echo $msgStr;
        } elseif ($type === 'uninstall') {
            $this->app->enqueueMessage(Text::_('MOD_ISHOP_COMPARE_XML_UNINSTALL_OK'), 'warning');
        }

        return true;
    }
}
