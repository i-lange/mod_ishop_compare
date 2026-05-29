<?php
/**
 * @package    mod_ishop_compare
 * @author     Pavel Lange <pavel@ilange.ru>
 * @link       https://github.com/i-lange/mod_ishop_compare
 * @copyright  (C) 2025 Pavel Lange <https://ilange.ru>
 * @license    GNU General Public License version 2 or later
 */

namespace Ilange\Module\Ishopcompare\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;

defined('_JEXEC') or die;

/**
 * Класс распаковщик
 * @since 1.0.0
 */
class Dispatcher extends AbstractModuleDispatcher
{
    /**
     * Возвращает данные для отображения
     * Метод родителя возвращает 'module', 'app', 'input', 'params', 'template'
     * @return array
     * @since 1.0.0
     */
    protected function getLayoutData()
    {
	    $data = parent::getLayoutData();

	    $wa = $data['app']->getDocument()->getWebAssetManager();
	    $wa->getRegistry()->addRegistryFile('media/mod_ishop_compare/joomla.asset.json');
	    $data['wa'] = $wa;

        $compare = $data['app']
            ->bootComponent('com_ishop')
            ->getMVCFactory()
            ->createModel('Compare', 'Site');

        $data['count'] = $compare->getCount();
	    unset($wa);
		unset($compare);

	    return $data;
    }
}
