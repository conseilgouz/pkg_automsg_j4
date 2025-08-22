<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * Version			: 4.0.0
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace ConseilGouz\Component\Automsg\Administrator\Controller;

\defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class MessageController extends FormController
{
    public function retry($pks = null, $state = 1, $userId = 0)
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        // Initialise variables.
        $app = Factory::getApplication();
        $model = $this->getModel('message');
        $input = $app->getInput();
        $pks = $input->post->get('cid', array(), 'array');
        $myid = $input->get('id');
        
        $item = $model->retry($pks);
        
        $this->setMessage(Text::_('COM_AUTOMSG_RETRIED'));
        $this->setRedirect(Uri::base().'index.php?option=com_automsg&view=message&layout=edit&id='.$myid);
        return true;

    }

    public function cancel($key = null)
    {
        // $result = parent::cancel();
        $app = Factory::getApplication();
        $return = Uri::base().'index.php?option=com_automsg&view=messages';
        $app->redirect($return);
        return true;
    }


}
