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
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\UserFactoryInterface;
use ConseilGouz\Automsg\Helper\Automsg as AutomsgHelper;

$comfield	= 'components/com_automsg/';
/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

$wa->addInlineStyle('.icon-error{ color:red!important}');

$wa->useScript('keepalive')
    ->useScript('form.validate');

$modelMessage = $this->getModel();
$data = $modelMessage->getMessagesList($this->message);

$app = Factory::getApplication();
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
       				<th class="70%">
                    Article(s)
				    </th>
				    <th class="5%">
                    Envoyé
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
                    <th class="5%">
                        Attente
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
    $modelArticle     = AutomsgHelper::prepare_content_model();
    foreach ($articles as $articleid) {
        $article = $modelArticle->getItem($articleid);
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
				<?php
				if ($cr) {
				    if ($cr->error > 0) { // build error modal
				        $errors = $modelMessage->getMessageErrors($message->sent);
				        echo '<a href="#" data-bs-toggle="modal" data-bs-target="#errorModal" title="Voir les erreurs">';
				        echo $cr->error;
				        echo '</a>';
				        echo '<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">';
				        echo '<div class="modal-dialog">';
				        echo '<div class="modal-content">';
				        echo '<div class="modal-header">';
				        echo '<h1 class="modal-title fs-5" id="errorModalLabel">Liste des erreurs</h1>';
				        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
				        echo '</div>';
				        echo '<div class="modal-body">';
				        foreach($errors as $error) {
				            $auser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($error->userid);
				            echo '<p>'.$auser->name.' (user id '.$auser->id.') : '.$error->error.'</p>';
				        }
				        echo '</div>';
				        echo '</div>';
				        echo '</div>';
				        echo '</div>';
				    }
				} ?>
				</td>
				<td class="center">
				<?php if ($cr) {
				    if ($cr->waiting > 0) { // build error modal
				        $errors = $modelMessage->getMessageWaiting($message->sent);
				        echo '<a href="#" data-bs-toggle="modal" data-bs-target="#errorModal" title="Voir en attente">';
				        echo $cr->waiting;
				        echo '</a>';
				        echo '<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">';
				        echo '<div class="modal-dialog">';
				        echo '<div class="modal-content">';
				        echo '<div class="modal-header">';
				        echo '<h1 class="modal-title fs-5" id="errorModalLabel">Liste des utilisateurs en attente</h1>';
				        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
				        echo '</div>';
				        echo '<div class="modal-body">';
				        foreach($errors as $error) {
				            $auser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($error->userid);
				            echo '<p>'.$auser->name.' (user id '.$auser->id.')</p>';
				        }
				        echo '</div>';
				        echo '</div>';
				        echo '</div>';
				        echo '</div>';
				    }
				} ?>
				</td>
               
                <td>
            <?php
				if ($message->state == 1) {
				    echo '<span class="icon-publish" aria-hidden="true" title="Envoyé"></span>';
				}
    if ($message->state == 9) {
        echo '<span class="icon-error" aria-hidden="true" title="Contient des erreurs"></span>';
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
