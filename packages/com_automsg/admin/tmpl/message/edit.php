<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * Version			: 4.0.0
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

// no direct access
\defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\Component\Content\Site\Model\ArticleModel;

$comfield	= 'components/com_automsg/';
/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

$wa->useScript('keepalive')
    ->useScript('form.validate');

$model = $this->getModel();
$data = $model->getMessagesList($this->message);

$user = Factory::getApplication()->getIdentity();
$userId		= $user->id;

$canCreate	= $user->authorise('core.create');
$canEdit	= $user->authorise('core.edit');
$canCheckin	= $user->authorise('core.manage', 'com_checkin') ;
$canChange	= $user->authorise('core.edit.state') && $canCheckin;

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

$listOrder	= '';
$listDirn	= '';

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

?>
<script type="text/javascript">
	Joomla.submitbutton = function(task){

	if (task == 'message.cancel' || document.formvalidator.isValid(document.adminForm)) {
			Joomla.submitform(task, document.getElementById('message-form'));
		}
}
</script>

<div class="span12">
<div class="nr-app nr-app-page">
	<form action="<?php echo Route::_('index.php?option=com_automsg&view=message&layout=edit&id='.(int) $this->message->id); ?>" method="post" name="adminForm"  class="form-validate" id="message-form" >
   	    <div class="form-horizontal">
            <table class="table table-striped" id="articleList">
          		<thead>
          			<tr>
       				<th class="90%">
                    <?php echo HTMLHelper::_('grid.sort', 'Article(s)', 'articles', '', ''); ?>
				    </th>
				    <th class="5%">
					<?php echo HTMLHelper::_('grid.sort', 'Envoyé', 'modified', '', ''); ?>
				    </th>
				    <th width="5%">
					<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'sent', '', ''); ?>
				    </th>
            	    </tr>
		        </thead>
                <tbody>
		        <?php
                $titles = "";

foreach ($data as $i => $message) : ?>
            		<tr class="row<?php echo $i % 2; ?>">
                    <td class="center">
                <?php
    $articles = explode(',', $message->articles);
    foreach ($articles as $articleid) {
        $article = $model->getItem($articleid);
        $titles .= ($titles) ? ',' : '' ;
        $titles .= $article->title;
    }
    echo $this->escape(HTMLHelper::_('string.truncateComplex', $titles, 200, true)); ?>
				</td>
                <td>
            <?php
		    $sent = $message->sent;
		    if (!$sent) {
		        $sent = "en attente";
		    }
		    echo $this->escape($sent)
		    ?>
                </td>
                <td>
            <?php
                echo HTMLHelper::_('jgrid.state', $states, $message->state, $i, 'messages.', $canChange, 'cb'); 
            ?>
                </td>
                </tr>
                <?php endforeach; ?> 
                </tbody>            
            </table>
       	</div>
        <?php echo HTMLHelper::_('form.token'); ?>
		<input type="hidden" name="task" value="" />
	</form>
</div>
</div>
