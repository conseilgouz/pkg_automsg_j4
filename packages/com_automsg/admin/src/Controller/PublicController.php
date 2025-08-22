<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace ConseilGouz\Component\Automsg\Administrator\Controller;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Uri\Uri;
use ConseilGouz\Component\Automsg\Administrator\Model\PublicModel;

/**
 * messages controller class
 */
class PublicController extends FormController
{
    protected $text_prefix = 'AUTOMSG';

    public function delete($pks = null)
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();
        $input = $app->getInput();
        $pks = $input->post->get('cid', array(), 'array');
        $model = new PublicModel();
        $model->delete($pks);
        $this->setRedirect(Uri::base().'index.php?option=com_automsg&view=public');
    }
    public function publish($pks = null)
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();
        $input = $app->getInput();
        $pks = $input->post->get('cid', array(), 'array');
        $model = new PublicModel();
        $model->publish($pks);
        $this->setRedirect(Uri::base().'index.php?option=com_automsg&view=public');
    }
    public function unpublish($pks = null)
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();
        $input = $app->getInput();
        $pks = $input->post->get('cid', array(), 'array');
        $model = new PublicModel();
        $model->unpublish($pks);
        $this->setRedirect(Uri::base().'index.php?option=com_automsg&view=public');
    }

}
