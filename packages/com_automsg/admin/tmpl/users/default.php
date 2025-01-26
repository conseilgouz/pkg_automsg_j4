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
$options[] = HTMLHelper::_('select.option', '1', Text::_('COM_AUTOMSG_USERS_SEND'));
$options[] = HTMLHelper::_('select.option', '0', Text::_('COM_AUTOMSG_USERS_DONTSEND'));


$states = [
    1 => [
        'task'           => 'unblock',
        'text'           => '',
        'active_title'   => 'COM_USERS_TOOLBAR_UNBLOCK',
        'inactive_title' => '',
        'tip'            => true,
        'active_class'   => 'unpublish',
        'inactive_class' => 'unpublish',
    ],
    0 => [
        'task'           => 'block',
        'text'           => '',
        'active_title'   => 'COM_USERS_TOOLBAR_BLOCK',
        'inactive_title' => '',
        'tip'            => true,
        'active_class'   => 'publish',
        'inactive_class' => 'publish',
    ],
];
$states_profile = [
    0 => [
        'task'           => 'unblock',
        'text'           => '',
        'active_title'   => 'COM_USERS_TOOLBAR_UNBLOCK',
        'inactive_title' => '',
        'tip'            => true,
        'active_class'   => 'unpublish',
        'inactive_class' => 'unpublish',
    ],
    1 => [
        'task'           => 'block',
        'text'           => '',
        'active_title'   => 'COM_USERS_TOOLBAR_BLOCK',
        'inactive_title' => '',
        'tip'            => true,
        'active_class'   => 'publish',
        'inactive_class' => 'publish',
    ],
];
?>
<form action="<?php echo Route::_('index.php?option=com_automsg&view=users'); ?>" method="post" name="adminForm" id="adminForm">
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
				<th class="10%">
					<?php echo HTMLHelper::_('grid.sort', Text::_('COM_USERS_HEADING_NAME'), 'name', $listDirn, $listOrder); ?>
				</th>
				<th class="10%">
					<?php echo HTMLHelper::_('grid.sort', Text::_('JGLOBAL_USERNAME'), 'username', $listDirn, $listOrder); ?>
				</th>
				<th class="5%">
					<?php echo HTMLHelper::_('grid.sort', Text::_('COM_AUTOMSG_USERS_PROFILE_VALUE'), 'value', $listDirn, $listOrder); ?>
				</th>
				<th class="5%">
					<?php echo Text::_('JGLOBAL_EMAIL');?>
				</th>
				<th class="5%">
					<?php echo Text::_('COM_USERS_HEADING_LAST_VISIT_DATE');?>
				</th>
				<th class="5%">
					<?php echo HTMLHelper::_('grid.sort', 'COM_USERS_HEADING_ENABLED', 'block', $listDirn, $listOrder); ?>
				</th>
				<th class="5%">
					<?php echo HTMLHelper::_('grid.sort', 'COM_USERS_HEADING_ACTIVATED', 'activation', $listDirn, $listOrder); ?>
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
		<?php foreach ($this->items as $i => $user) :
		    ?>
			<tr class="row<?php echo $i % 2; ?>">
                <td>
                <?php
		            $name = $user->name;
                    echo $this->escape($name)
                ?>
                </td>
                <td>
                <?php
		            $name = $user->username;
                    echo $this->escape($name)
		    ?>
                </td>
				<td class="center">
                <?php
                    echo HTMLHelper::_('jgrid.state', $states_profile, $user->value, $i, 'users.', false, 'cb');
                ?>
				</td>
				<td class="center">
				<?php
                    echo $user->email;
                ?>
				</td>
				<td class="center">
				<?php
                    if ($user->lastvisitDate) {
                        echo HTMLHelper::_('date.relative', $user->lastvisitDate);
                    }
                ?>
				</td>
				<td class="center">
				<?php
                    echo HTMLHelper::_('jgrid.state', $states, $user->block, $i, 'users.', false, 'cb')
                ?>
				</td>
				<td class="center">
                <?php
                    $valid = 1;
                    if (! $user->activation) { // activation done
                        $valid = 0;
                    }
                    echo HTMLHelper::_('jgrid.state', $states, $valid, $i, 'users.', false, 'cb');
                // echo $user->activation;
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

