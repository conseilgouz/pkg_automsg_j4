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

/** @var Joomla\Component\Users\Site\View\Profile\HtmlView $this */

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

// Load user_profile plugin language
$lang = Factory::getLanguage();
$lang->load('plg_user_profile', JPATH_ADMINISTRATOR);

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

?>
<div class="com-users-profile__edit profile-edit">

    <form id="member-profile" action="<?php echo Route::_('index.php?option=com_automsg'); ?>" method="post" class="com-users-profile__edit-form form-validate form-horizontal well" enctype="multipart/form-data">
        <?php // Iterate through the form fieldsets and display each one. ?>
        <?php foreach ($this->form->getFieldsets() as $group => $fieldset) : ?>
            <?php $fields = $this->form->getFieldset($group); ?>
            <?php if (count($fields)) : ?>
                <fieldset>
                    <?php // If the fieldset has a label set, display it as the legend. ?>
                    <?php if (isset($fieldset->description) && trim($fieldset->description)) : ?>
                        <p>
                            <?php echo $this->escape(Text::_($fieldset->description)); ?>
                        </p>
                    <?php endif; ?>
                    <?php // Iterate through the fields in the set and display them. ?>
                    <?php foreach ($fields as $field) {
                        if ($field->fieldname == "name") {
                            echo Text::sprintf('COM_AUTOMSG_NOTE',$field->value);
                        } else {
                            echo $field->renderField();
                        }
                    } ?>
                </fieldset>
            <?php endif; ?>
        <?php endforeach; ?>

         <div class="com-users-profile__edit-submit control-group">
            <div class="controls">
                <button type="submit" class="btn btn-primary validate" name="task" value="automsg.save">
                    <span class="icon-check" aria-hidden="true"></span>
                    <?php echo Text::_('JSAVE'); ?>
                </button>
                <button type="submit" class="btn btn-danger" name="task" value="automsg.cancel" formnovalidate>
                    <span class="icon-times" aria-hidden="true"></span>
                    <?php echo Text::_('JCANCEL'); ?>
                </button>
                <input type="hidden" name="option" value="com_automsg">
            </div>
        </div>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
