<?php
/**
 * @package    mod_ishop_compare
 * @author     Pavel Lange <pavel@ilange.ru>
 * @link       https://github.com/i-lange/mod_ishop_compare
 * @copyright  (C) 2025 Pavel Lange <https://ilange.ru>
 * @license    GNU General Public License version 2 or later
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Ilange\Component\Ishop\Site\Helper\RouteHelper;

defined('_JEXEC') or die;

/**
 * Список доступных переменных
 * @var stdClass $module
 * @var Joomla\CMS\Application\CMSApplicationInterface $app
 * @var Joomla\Input\Input $input
 * @var Joomla\Registry\Registry $params
 * @var stdClass $template
 * @var Joomla\CMS\WebAsset\WebAssetManager $wa
 * @var string $captcha
 * @var int $count
 * @var int $wishlist
 */

if ($params->get('use_js', 0)) {
    $wa->useScript('mod_ishop_compare.front');
}

if ($params->get('use_css', 0)) {
    $wa->useStyle('mod_ishop_compare.front');
}

// Приводим счетчик к безопасному числу перед выводом в HTML.
$safeCount = max(0, (int) $count);

// Готовим стабильный идентификатор экземпляра для будущей JS-логики очистки.
$moduleId = isset($module->id) ? (int) $module->id : 0;
$compareId = 'mod-ishop-compare-' . $moduleId;

// Экранируем маршрут и доступное имя перед выводом в атрибуты HTML.
$compareUrl = htmlspecialchars(Route::_(RouteHelper::getCompareRoute()), ENT_QUOTES, 'UTF-8');
$compareLabel = htmlspecialchars(Text::_('MOD_ISHOP_COMPARE_TEXT'), ENT_QUOTES, 'UTF-8');
?>
<a href="<?php echo $compareUrl; ?>"
   id="<?php echo $compareId; ?>"
   class="mod_ishop_compare"
   data-ishop-compare
   data-ishop-compare-id="<?php echo $moduleId; ?>"
   aria-label="<?php echo $compareLabel; ?>">
    <div class="wrap">
        <?php if ($params->get('show_text', 0)) : ?>
            <span class="text"><?php echo Text::_('MOD_ISHOP_COMPARE_TEXT'); ?></span>
        <?php endif; ?>
        <?php if ($params->get('show_count', 0)) : ?>
            <small class="count"><?php echo $safeCount; ?></small>
            <span><?php echo Text::_('MOD_ISHOP_COMPARE_COUNT'); ?></span>
        <?php endif; ?>
    </div>
</a>
<?php if ($params->get('show_btn_clear', 0)) : ?>
    <button class="btn" type="button"
            data-ishop-compare-clear
            data-ishop-compare-target="#<?php echo $compareId; ?>">
        <?php echo Text::_('MOD_ISHOP_COMPARE_CLEAR'); ?>
    </button>
<?php endif; ?>
