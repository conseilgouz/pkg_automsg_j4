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

$user       = Factory::getApplication()->getIdentity();
$canCheckin	= $user->authorise('core.manage', 'com_checkin') ;
$canChange  = $user->authorise('core.edit.state') && $canCheckin;

$states = [
        0 => [
             '', // action : retry => envoi
             '',
             '',
             '',
             true,
             'error', // icone
             '',
         ],
         9 => [
              '', // restart
              '',
              'Erreurs', // état :
              '',
              true,
              'error', // icone
              '',
         ]
       ];
?>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.button-envelope-opened').setAttribute('disabled','');
    })
	Joomla.submitbutton = function(task){
	if (task == 'message.retry' || task == 'message.cancel' || document.formvalidator.isValid(document.adminForm)) {
			Joomla.submitform(task, document.getElementById('adminForm'));
		}
    }
    Joomla.isChecked = function(task) {
        boxs =document.querySelectorAll('.form-check-input');
        ischecked = false;
        for (var j=0; j<boxs.length; j++) {
            if (boxs[j].checked) ischecked = true;
        }    
        button = document.querySelector('.button-envelope-opened');
        if (ischecked) button.removeAttribute('disabled')
        else button.setAttribute('disabled','')
    }

</script>

<div class="span12">
<div class="nr-app nr-app-page">
	<form action="<?php echo Route::_('index.php?option=com_automsg&view=message&layout=edit&id='.(int) $this->message->id); ?>" method="post" name="adminForm" id="adminForm" >
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
                    foreach ($data as $i => $message) : 
                     ?>
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
                        echo $this->escape(HTMLHelper::_('string.truncateComplex', $titles, 200, true)); 
                    ?>
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
				<?php 
                    if ($cr) {
                        echo $cr->total;
                    }
                ?>
				</td>
				<td class="center">
				<?php 
                    if ($cr) {
                        echo $cr->sent;
                    } 
                ?>
				</td>
				<td class="center">
				<?php
                    if ($cr) {
                        if ($cr->error > 0) { // build error modal
                            $errors = $modelMessage->getMessageErrors($message->sent);
                            echo '<a data-bs-toggle="collapse" href="#errorCollapse" aria-expanded="true" title="Voir les erreurs">';
                            echo $cr->error;
                            echo '</a>';
                        }
                    } 
                ?>
				</td>
				<td class="center">
				<?php 
                    if ($cr) {
                        if ($cr->waiting > 0) { // build error modal
                            $errors = $modelMessage->getMessageWaiting($message->sent);
                            echo '<a  data-bs-toggle="collapse" href="#waitingCollapse" aria-expanded="false" title="Voir en attente">';
                            echo $cr->waiting;
                            echo '</a>';
                        }
                    } 
                ?>
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
                <?php 
                endforeach; 
                ?> 
                </tbody>
            </table>
       	</div>
        <?php 
            $show= "";
            if (($cr) && ($cr->error > 0)) {
                $show = " show";
            }
            ?>
        <div class="collapse <?php echo $show;?> " id="errorCollapse"  tabindex="-1" aria-labelledby="errorCollapseLabel" aria-hidden="true">
        <b>Détail des erreurs</b><br>(vous pouvez ré-essayer jusqu'à 3 fois de les renvoyer : sélectionner l'erreur en cochant sa case et cliquer sur le bouton <b>Nouvel essai</b>)
            <div class="card card-body">
                <div class="row">
            <?php
            $i = 0;
                foreach($errors as $error) {
                    $auser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($error->userid);
                    echo '<div class="col-1">';
                    if ($error->retry < 3) {
                        echo HTMLHelper::_('grid.id', $i, $error->id);
                    } else {
                        echo '<span class="icon-error" aria-hidden="true" title="3 essais faits"></span>';
                    }
                    echo '</div>';
                    echo '<div class="col-10">'.$auser->name.' (user id '.$auser->id.') : '.$error->error.'</div>';
                    echo '<div class="col-1">'.HTMLHelper::_('jgrid.state', $states, $message->state, $error->id, 'message.', $canChange, 'cb').'</div>';
                    $i++;

                }
            ?>
                </div>
            </div>
		</div>
        <div class="collapse" id="waitingCollapse" tabindex="-1" aria-labelledby="waitingCollapseLabel" aria-hidden="true">
        Détail des 'en attente'
            <div class="card card-body">
                <div class="row">
                <?php
                foreach($errors as $error) {
                    $auser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($error->userid);
                    echo '<div class="col-11">'.$auser->name.' (user id '.$auser->id.')</div>';
                }
                ?>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('form.token'); ?>
		<input type="hidden" name="task" value="" />
	</form>
</div>
</div>
