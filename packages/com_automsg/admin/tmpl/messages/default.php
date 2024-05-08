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
use ConseilGouz\Automsg\Helper\Automsg as AutomsgHelper;

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
             '',
             'Cliquer pour envoyer',
             '',
             true,
             'warning', // icone
             '',
         ],
         1 => [
              'detail', // ne rien faire
              '',
              'Envoyé', // état :
              '',
              true,
              'publish', // icone
              '',
         ],
         9 => [
              'restart', // restart
              '',
              'Erreurs', // état :
              '',
              true,
              'error', // icone
              '',
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
		<div class="btn-group pull-right hidden-phone">
			<select name="filter_state" class="inputbox" onchange="this.form.submit()">
				<option value=""><?php echo Text::_('JOPTION_SELECT_PUBLISHED');?></option>
                <?php echo HtmlHelper::_('select.options', $options, 'value', 'text', $this->state->get('filter.state'), true);?>
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
				<th class="5%">
					Total
				</th>
				<th class="5%">
					OK
				</th>
				<th class="5%">
					Erreurs
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
		    $model     = AutomsgHelper::prepare_content_model();
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
				<?php
		        $cr = null;
                if (isset($message->cr)) {
                    $cr = json_decode($message->cr);
                }
                ?>
				<td class="center">
					<?php if ($cr) {
					    echo $cr->total;
					} ?>
				</td>
				<td class="center">
				<?php if ($cr) {
				    echo $cr->sent;
				} ?>
				</td>
				<td class="center">
				<?php if ($cr) {
				    echo $cr->error;
				} ?>
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

