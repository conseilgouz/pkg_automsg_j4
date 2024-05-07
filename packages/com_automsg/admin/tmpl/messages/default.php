<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * Version			: 4.0.0
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Model\ArticleModel;
use Joomla\Registry\Registry;

HTMLHelper::_('behavior.multiselect');

$user = Factory::getApplication()->getIdentity();
$userId		= $user->id;
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$canOrder	= ContentHelper::getActions('com_automsg');
$saveOrder	= $listOrder == 'ordering';

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getDocument()->getWebAssetManager();
$wa->addInlineStyle('.icon-error{ color:red!important}');


$states = [
        0 => [
             'send', // action : publish => envoi
             'aa',
             'Cliquer pour envoyer',
             'bb',
             true,
             'warning', // icone
             'cc',
         ],
         1 => [
              'ss', // ne rien faire
              'sss',
              'Envoyé', // état :
              'ssss',
              true,
              'publish', // icone
              'sssss',
         ],
         9 => [
              'restart', // ne rien faire
              'fffff',
              'Erreurs', // état :
              'ffff',
              true,
              'error', // icone
              'ff',
         ]
       ];
$options   = [];
$options[] = HTMLHelper::_('select.option', '0', 'En attente');
$options[] = HTMLHelper::_('select.option', '1', 'Envoyés');
$options[] = HTMLHelper::_('select.option', '9', 'En erreur');

$ordering	= ($listOrder == 'ordering');
$canCreate	= $user->authorise('core.create');
$canEdit	= $user->authorise('core.edit');
$canCheckin	= $user->authorise('core.manage', 'com_checkin') ;
$canChange	= $user->authorise('core.edit.state') && $canCheckin;

?>
<form action="<?php echo Route::_('index.php?option=com_automsg&view=messages'); ?>" method="post" name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
	<?php else : ?>
	<div id="j-main-container">
	<?php endif; ?>
	<div id="filter-bar" class="btn-toolbar">
		<div class="filter-search btn-group pull-left">
			<label for="filter_search" class="element-invisible"><?php echo Text::_('JSEARCH_FILTER_LABEL'); ?></label>  
			<input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo Text::_('COM_AUTMSG_SEARCH_IN_TITLE'); ?>" />
        </div>
        <div class="btn-group pull-left">            
			<button type="submit" class="btn hasTooltip btn-primary">
                <span class="filter-search-bar__button-icon icon-search" aria-hidden="true"></span>
            </button>
			<button type="button" style="margin-left:1em;margin-right:1em" class="btn hasTooltip btn-primary " onclick="resetSearch(); this.form.submit();"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>
		<div class="btn-group pull-right hidden-phone">
			<select name="filter_state" class="inputbox" onchange="this.form.submit()">
				<option value=""><?php echo Text::_('JOPTION_SELECT_PUBLISHED');?></option>
                <?php echo HtmlHelper::_('select.options', $options);?>
			</select>
		</div>
	</div>
	<div class="clr"> </div>

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-no-items">
            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
        </div>
    <?php else : ?>   
    <table class="table table-striped" id="articleList">
		<thead>
			<tr>
				<th width="1%">
					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
				</th>
				<th class="90%">
					<?php echo HtmlHelper::_('grid.sort', 'Article(s)', 'articles', $listDirn, $listOrder); ?>
				</th>
				<th class="5%">
					<?php echo HtmlHelper::_('grid.sort', 'Envoyé', 'modified', $listDirn, $listOrder); ?>
				</th>
				<th width="5%">
					<?php echo HtmlHelper::_('grid.sort', 'JSTATUS', 'sent', $listDirn, $listOrder); ?>
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
		<?php foreach ($this->items as $i => $message) :
		    ?>
			<tr class="row<?php echo $i % 2; ?>">
				<td class="center">
					<?php 
                    // note : $message->ids contains all ids from one record
                    // id will be truncated to 1st record when sent to MessageModel
                    echo HtmlHelper::_('grid.id', $i, $message->ids);
                    ?>
				</td>
                <td>
            <?php
		    $model     = new ArticleModel(array('ignore_request' => true));
		    $model->setState('params', ComponentHelper::getParams('com_content'));
		    $model->setState('list.start', 0);
		    $model->setState('list.limit', 1);
		    $model->setState('filter.published', 1);
		    $model->setState('filter.featured', 'show');
		    // Access filter
		    $access = ComponentHelper::getParams('com_content')->get('show_noauth');
		    $model->setState('filter.access', $access);
		    // Ordering
		    $model->setState('list.ordering', 'a.hits');
		    $model->setState('list.direction', 'DESC');
		    $titles = "";
		    $articles = explode(',', $message->articles);
		    foreach ($articles as $articleid) {
		        $article = $model->getItem($articleid);
		        $titles .= ($titles) ? ',' : '' ;
		        $titles .= $article->title;
		    }
		    echo $this->escape(HTMLHelper::_('string.truncateComplex', $titles, 200, true)); ?>
				</td>
                <td >
            <?php
		    $sent = $message->sent;
		    if (!$sent) {
		        $sent = "en attente";
		    }
		    echo $this->escape($sent)
		    ?>
                </td>
				<td class="center">
					<?php

		            echo HTMLHelper::_('jgrid.state', $states, $message->state, $i, 'messages.', $canChange, 'cb'); ?>
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
<script>
function resetSearch() {
  document.getElementById("filter_search").value = "";
}
</script>

