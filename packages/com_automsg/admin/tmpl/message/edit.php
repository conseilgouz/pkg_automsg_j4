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

$wa->addInlineStyle('.icon-error{ color:red!important}');

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
                    Article(s)
				    </th>
				    <th class="5%">
                    Envoyé
				    </th>
				    <th width="5%">
					Status
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
                if ($message->state == 1) {
                    echo '<span class="icon-publish" aria-hidden="true" title="Envoyé"></span>';
                }
                if ($message->state == 9) { // build error modal
                    echo '<a href="#" data-bs-toggle="modal" data-bs-target="#errorModal" >';
                    echo '<span class="icon-error" aria-hidden="true" title="Voir les erreur"></span>';
                    echo '</a>';
                    echo '<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">';
                    echo '<div class="modal-dialog">';
                    echo '<div class="modal-content">';
                    echo '<div class="modal-header">';
                    echo '<h1 class="modal-title fs-5" id="errorModalLabel">Liste des erreurs</h1>';
                    echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                    echo '</div>';
                    echo '<div class="modal-body">';
                    echo '<p>Tagada</p>';
                    echo '<p>Tagada</p>';
                    echo '<p>Tsouin tsouin</p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
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
