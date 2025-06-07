<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect');

$user = Factory::getApplication()->getIdentity();
$userId		= $user->id;
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$canOrder	= ContentHelper::getActions('com_automsg');
$saveOrder	= $listOrder == 'ordering';

$ordering	= ($listOrder == 'ordering');

$app = Factory::getApplication();
$lang = $app->getLanguage();
$lang->load('com_users');

$options   = [];
$options[] = HTMLHelper::_('select.option', '1', Text::_('COM_AUTOMSG_ACTIVATED'));
$options[] = HTMLHelper::_('select.option', '0', Text::_('COM_AUTOMSG_DESACTIVATED'));

$states = [
    0 => [
        'task'           => 'unpublish',
        'text'           => '',
        'active_title'   => 'COM_USERS_TOOLBAR_UNBLOCK',
        'inactive_title' => '',
        'tip'            => true,
        'active_class'   => 'unpublish',
        'inactive_class' => 'unpublish',
    ],
    1 => [
        'task'           => 'publish',
        'text'           => '',
        'active_title'   => 'COM_USERS_TOOLBAR_BLOCK',
        'inactive_title' => '',
        'tip'            => true,
        'active_class'   => 'publish',
        'inactive_class' => 'publish',
    ],
];
?>
<form action="<?php echo Route::_('index.php?option=com_automsg&view=public'); ?>" method="post" name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
	<?php else : ?>
	<div id="j-main-container">
	<?php endif; ?>
<div  class="container mb-2">
    <div class="row">
        <div id="filter-bar" class="btn-toolbar col-sm-3">
            <div class="btn-group pull-right hidden-phone">
                <select name="filter_state" class="inputbox" onchange="this.form.submit()">
				<option value=""><?php echo Text::_('JOPTION_SELECT_PUBLISHED');?></option>
                <?php echo HTMLHelper::_('select.options', $options, 'value', 'text', $this->state->get('filter.state'), true);?>
                </select>
            </div>
        </div>
        <div id="task-status" class=" col-sm">
            <?php echo $this->taskstatus;?>
        </div>
    </div>
</div>
    
	<div class="clr"> </div>

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-no-items">
            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
        </div>
    <?php else : ?>   
    <table class="table table-striped" id="usersList">
		<thead>
			<tr>
				<th width="1%">
					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
				</th>
				<th class="5%">
					<?php echo Text::_('JGLOBAL_EMAIL');?>
				</th>
				<th class="5%">
					<?php echo Text::_('COM_AUTOMSG_IP');?>
				</th>
				<th class="1%">
					<?php echo Text::_('COM_AUTOMSG_COUNTRY');?>
				</th>
				<th class="5%">
					<?php echo HTMLHelper::_('grid.sort', 'COM_AUTOMSG_CREATED', 'created', $listDirn, $listOrder); ?>
				</th>
				<th class="5%">
					<?php echo HTMLHelper::_('grid.sort', 'COM_AUTOMSG_MODIFIED', 'modified', $listDirn, $listOrder); ?>
				</th>
				<th class="5%">
					<?php echo HTMLHelper::_('grid.sort', 'COM_AUTOMSG_STATE', 'state', $listDirn, $listOrder); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="13">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $public) :
		    ?>
			<tr class="row<?php echo $i % 2; ?>">
   				<td class="center">
					<?php echo HTMLHelper::_('grid.id', $i, $public->id); ?>
				</td>
				<td class="center">
				<?php
		            echo $public->email;
		    ?>
				</td>
				<td class="center">
				<?php
		        echo $public->ip;
		    ?>
				</td>
				<td class="center">
					<?php if ($public->country) {
                            echo HTMLHelper::_('image', 'com_automsg/' . strtolower($public->country) . '.png', $public->country, "title=$public->country", true);
                            }
                    ?>
				</td>
				<td class="center">
				<?php
		        if ($public->created) {
		            echo HTMLHelper::_('date.relative', $public->created);
		        }
		    ?>
				</td>
				<td class="center">
				<?php
		        if ($public->modified) {
		            echo HTMLHelper::_('date.relative', $public->modified);
		        }
		    ?>
				</td>
				<td class="center">
				<?php
		        echo HTMLHelper::_('jgrid.state', $states, $public->state, $i, 'public.', false, 'cb')
		    ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
    <?php endif; ?> 
	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HtmlHelper::_('form.token'); ?>
	</div>
	</div>
</form>

