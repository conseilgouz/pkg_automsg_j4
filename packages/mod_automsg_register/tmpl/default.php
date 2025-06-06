<?php
/**
 * @package AutoMsg
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 *
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Utilities\IpHelper;
use ConseilGouz\Module\AutomsgRegister\Site\Helper\AutomsgregisterHelper;

$app = Factory::getApplication();
$user = $app->getIdentity();
if (!$user->guest) {// only for guest
    return;
}
PluginHelper::importPlugin('automsg');
$response = false;
$contentEventArguments = [
    'context' => 'com_automsg.register',
    'params'  => $params,
    'response'    => &$response,
];
Factory::getApplication()->triggerEvent('onAutoMsgStart', $contentEventArguments);
if ($response) { // error found in plugins
    echo $response;
    return false;
}

$modulefield	= 'media/mod_automsg_register/';

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();

if ($params->get('css')) {
    $wa->addInlineStyle($params->get('css'));
}
$wa->registerAndUseScript('automsgregister'.$module->id, $modulefield.'js/init.js');

$ip = IpHelper::getIp();

$email = AutomsgregisterHelper::getEmailByIP($ip);

?>
<?php if ($params->get('pretext', '')) {?>
<div class="automsg_register_pretext pretext">
    <p><?php echo $params->get('pretext', ''); ?></p>
</div>
<?php } ?>
<div id="automsg_register" style="text-align:center;min-width:4em">
    <input  id="automsg_register_email" type="email" name="email" class="form-control" autocomplete="email" 
            placeholder="<?php echo Text::_('AUTOMSG_REGISTER_EMAIL'); ?>"
            value="<?php echo $email ? $email : '';?>">
    <p id="automsg_register_msg" style='display:none;text-align:center'></p>
    <button class="btn"  id="automsg_register_btn"  title="<?php echo Text::_('AUTOMSG_BTN_DESC'); ?>" style="margin-top:10px">
            <?php 
            if ($email) {
                echo Text::_('AUTOMSG_REGISTER_UPDATEBTN');
            } else {
                echo Text::_('AUTOMSG_REGISTER_EMAILBTN');
            }
            ?>
    </button>
</div>
<input type="hidden" id="automsg_register_id" value="<?php echo $module->id;?>" />
<input type="hidden" id="timestp" value="<?php echo strtotime("now");?>" />
<?php echo HTMLHelper::_('form.token'); ?>

