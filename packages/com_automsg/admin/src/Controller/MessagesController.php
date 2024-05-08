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
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Scheduler\Administrator\Scheduler\Scheduler;
use Joomla\Database\DatabaseInterface;
use ConseilGouz\Component\Automsg\Administrator\Model\ConfigModel;

/**
 * messages controller class
 */
class MessagesController extends FormController
{
    protected $text_prefix = 'AUTOMSG';
    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        // Initialise variables.
        $app = Factory::getApplication();
        $model = $this->getModel('messages');
        $data = $app->input->getRaw('jform', array(), 'post', 'array');
        $form = $model->getForm();
        $data['name'] = 'annonce';
        $res = $model->validate($form, $data);
        // Attempt to save the data.
        if (!$model->save($res)) {
            // Redirect back to the edit screen.
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_automsg&view=messages', false));
            return false;
        }
        $this->setMessage(Text::_('JLIB_APPLICATION_SAVE_SUCCESS'));
        $this->setRedirect(Route::_('index.php?option=com_automsg&view=messages', true));
        return true;
    }
    public function detail($pks = null, $state = 0, $userId = 0)
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        // Initialise variables.
        $app = Factory::getApplication();
        $model = $this->getModel('messages');

        $input = $app->input;
        $pks = $input->post->get('cid', array(), 'array');

        $this->setRedirect(Uri::base().'index.php?option=com_automsg&view=message&layout=edit&id='.$pks[0]);
        return true;

    }
    public function restart($pks = null, $state = 1, $userId = 0)
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        // Initialise variables.
        $app = Factory::getApplication();
        $model = $this->getModel('messages');

        $input = $app->input;
        $pks = $input->post->get('cid', array(), 'array');
        $item = $model->restart($pks);
        $this->setMessage(Text::_('COM_AUTOMSG_RESTARTED'));
        $this->setRedirect(Uri::base().'index.php?option=com_automsg&view=messages');
        return true;

    }
    public function send($pks = null)
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();
        $input = $app->input;
        $pks = $input->post->get('cid', array(), 'array');
        // check for errors to restart
        $model = $this->getModel('messages');
        $articles = $model->check_restart($pks);
        if (sizeof($articles)) {
            $this->restart($articles);
        }
        // check for waiting async
        $model = new ConfigModel();
        $params = $model->getItem(1);
        if ($params->async > 0) {
            $this->send_async();
        }
        $this->setRedirect(Uri::base().'index.php?option=com_automsg&view=messages');
    }
    private function send_async()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $result = $db->setQuery(
            $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__scheduler_tasks'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('automsg'))
                ->where($db->quoteName('state') . '= 1')
        )->loadResult();
        if (!$result) {
            $this->setMessage(Text::_('COM_AUTOMSG_NOASK'));
            $this->setRedirect(Uri::base().'index.php?option=com_automsg&view=messages');
            return;
        }
        $id             =  $result;
        $allowConcurrent = Factory::getApplication()->getInput()->getBool('allowConcurrent', false);
        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.testrun', 'com_scheduler.task.' . $id)) {
            throw new \Exception(Factory::getApplication()->getLanguage()->_('JERROR_ALERTNOAUTHOR'), 403);
        }
        /**
         * ?: About allow simultaneous, how do we detect if it failed because of pre-existing lock?
         *
         * We will allow CLI exclusive tasks to be fetched and executed, it's left to routines to do a runtime check
         * if they want to refuse normal operation.
         */
        $task = (new Scheduler())->getTask(
            [
                'id'               => $id,
                'allowDisabled'    => true,
                'bypassScheduling' => true,
                'allowConcurrent'  => $allowConcurrent,
            ]
        );

        if ($task) {
            $task->run();
            $this->setMessage(Text::_('COM_AUTOMSG_TASK_SUCCESS'));
        } else {
            /**
             * Placeholder result, but the idea is if we failed to fetch the task, it's likely because another task was
             * already running. This is a fair assumption if this test run was triggered through the administrator backend,
             * so we know the task probably exists and is either enabled/disabled (not trashed).
             */
            $this->setMessage(Text::_('COM_AUTOMSG_TASK_ERROR'));
        }
        $this->setRedirect(Uri::base().'index.php?option=com_automsg&view=messages');
    }
}
