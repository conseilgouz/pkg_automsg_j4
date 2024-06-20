<?php
/**
 * Automsg Component  - Joomla 4.x/5.x Component 
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2023 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

/** @var Joomla\Component\Users\Site\View\Profile\HtmlView $this */
HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

$params = ComponentHelper::getParams('com_automsg'); 

?>
<legend><?php echo $params['legend']; ?></legend>
<div class="automsg_cancel row">
    <div class="col-md-2"></div>
	<div class="automsg_cancel col-md-5" style="margin-top:1em" >
	<?php echo Text::_('COM_AUTOMSG_CANCEL'); ?>
	</div>
	<div class="col-md-1"></div>
	<div class="col-md-4" style="margin-top:1em"><a href="index.php"  rel="noopener noreferrer" class="btn btn-primary"><?php echo Text::_('COM_AUTOMSG_HOME');?></a></div>
</div>
