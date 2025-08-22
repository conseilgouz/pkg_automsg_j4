<?php
/**
 * Automsg Component  - Joomla 4.x/5.x Component
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2023 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var Joomla\Component\Users\Site\View\Profile\HtmlView $this */

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

$input = Factory::getApplication()->getInput();
$email = $input->getRaw('email');
if ($email) {
    $model = $this->getModel();
    $res = $model->deletePublic($email);
} else {
    $email = "";
}
$params = ComponentHelper::getParams('com_automsg'); 
?>
<legend><?php echo $params['legend']; ?></legend>
<div class="automsg_register_cancel row">
    <div class="col-md-2"></div>
	<div class="automsg_notfound col-md-5" style="margin-top:1em" >
	<?php echo Text::sprintf('COM_AUTOMSG_REGISTER_CANCELED', $email); ?>
	</div>
	<div class="col-md-1"></div>
	<div class="col-md-4" style="margin-top:1em"><a href="index.php" class="btn btn-primary" rel="noopener noreferrer"><?php echo Text::_('COM_AUTOMSG_HOME');?></a></div>
</div>
