<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * Version			: 4.0.0
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/

namespace ConseilGouz\Component\Automsg\Administrator\Controller;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Annonce controller class
 */
class HistosController extends FormController
{
    protected $text_prefix = 'CGPROS';
    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        // Initialise variables.
        $app = Factory::getApplication();
        $model = $this->getModel('histos');
        $data = $app->input->getRaw('jform', array(), 'post', 'array');
        $form = $model->getForm();
        $data['name'] = 'annonce';
        $res = $model->validate($form, $data);
        // Attempt to save the data.
        if (!$model->save($res)) {
            // Redirect back to the edit screen.
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_automsg&view=histos', false));
            return false;
        }
        $this->setMessage(Text::_('JLIB_APPLICATION_SAVE_SUCCESS'));
        $this->setRedirect(Route::_('index.php?option=com_automsg&view=histos', true));
        return true;
    }
    public function unpublish($pks = null, $state = 0, $userId = 0)
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        // Initialise variables.
        $app = Factory::getApplication();
        $model = $this->getModel('histos');

        $input = $app->input;
        $pks = $input->post->get('cid', array(), 'array');
        if (!$model->publish($pks, $state)) {
        }
        $this->setMessage(Text::_('COM_AUTOMSG_UNPUBLISHED'));
        $this->setRedirect(Route::_('index.php?option=com_automsg&view=histos', true));
        return true;

    }
    public function publish($pks = null, $state = 1, $userId = 0)
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        // Initialise variables.
        $app = Factory::getApplication();
        $model = $this->getModel('histos');

        $input = $app->input;
        $pks = $input->post->get('cid', array(), 'array');
        if (!$model->publish($pks, $state)) {
        }
        $this->setMessage(Text::_('COM_AUTOMSG_PUBLISHED'));
        $this->setRedirect(Route::_('index.php?option=com_automsg&view=histos', true));
        return true;

    }
}
