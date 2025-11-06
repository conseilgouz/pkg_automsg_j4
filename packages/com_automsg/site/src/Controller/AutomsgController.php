<?php
/**
 * Automsg Component  - Joomla 4.x/5.x Component 
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2023 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/
namespace ConseilGouz\Component\Automsg\Site\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Profile controller class for Users.
 *
 * @since  1.6
 */
class AutomsgController extends BaseController
{
    /**
     * Method to check out a user for editing and redirect to the edit form.
     *
     * @return  boolean
     *
     * @since   1.6
     */
    public function edit()
    {
        $app         = $this->app;
        // Get the current user id.
        $userId     = $this->getInput()->getInt('userid');

        $app->setUserState('com_automsg.edit.automsg.id', $userId);

        // Redirect to the edit screen.
        $this->setRedirect(Route::_('index.php?option=com_automsg&view=automsg&layout=edit', false));

        return true;
    }

    /**
     * Method to save a user's profile data.
     *
     * @return  void|boolean
     *
     * @since   1.6
     * @throws  \Exception
     */
    public function save()
    {
        // Check for request forgeries.
        $this->checkToken();

        $app    = $this->app;

        /** @var \Joomla\Component\Users\Site\Model\ProfileModel $model */
        $model  = $this->getModel('Automsg', 'Site');
        // Get the user data.
        $requestData = $app->getInput()->post->get('jform', [], 'array');

        // Validate the posted data.
        $form = $model->getForm();

        if (!$form) {
            throw new \Exception($model->getError(), 403);
        }

        // Send an object which can be modified through the plugin event
        $objData = (object) $requestData;
        $app->triggerEvent(
            'onContentNormaliseRequestData',
            ['com_automsg.automsg', $objData, $form]
        );
        $requestData = (array) $objData;

        // Validate the posted data.
        $data = $model->validate($form, $requestData);

        // Check for errors.
        if ($data === false) {
            // Get the validation messages.
            $errors = $model->getErrors();

            // Push up to three validation messages out to the user.
            for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
                if ($errors[$i] instanceof \Exception) {
                    $app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                } else {
                    $app->enqueueMessage($errors[$i], 'warning');
                }
            }

            // Save the data in the session.
            $app->setUserState('com_automsg.edit.automsg.data', $requestData);

            // Redirect back to the edit screen.
            $userId = (int) $app->getUserState('com_automsg.edit.automsg.id');
            $automsgtoken = $this->getTokenFromId($userId);
            $this->setRedirect(Route::_('index.php?option=com_automsg&view=automsg&layout=edit&token=' . $automsgtoken, false));

            return false;
        }

        // Attempt to save the data.
        $return = $model->save($data);
        
        if ($return) $automsgtoken = $this->getTokenFromId($return);
        // Check for errors.
        if ($return === false) {
            // Save the data in the session.
            $app->setUserState('com_automsg.edit.automsg.data', $data);

            // Redirect back to the edit screen.
            $userId = (int) $app->getUserState('com_automsg.edit.automsg.id');
            $this->setMessage(Text::sprintf('COM_USERS_PROFILE_SAVE_FAILED', $model->getError()), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_automsg&view=automsg&layout=edit&token=' . $automsgtoken, false));

            return false;
        }

        $this->setMessage(Text::_('COM_AUTOMSG_SAVE_SUCCESS'));
        $redirect = 'index.php?option=com_automsg&view=automsg&layout=complete';
        $this->setRedirect(Route::_($redirect, false));

        // Flush the data from the session.
        $app->setUserState('com_automsg.edit.automsg.data', null);
    }
    protected function getTokenFromId($userid) {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
        ->select($db->quoteName('profile_value'))
        ->from($db->quoteName('#__user_profiles'))
        ->where($db->quoteName('profile_key') . ' like ' .$db->quote('profile_automsg.token').' AND '.$db->quoteName('user_id'). ' = '.$userid);
        $db->setQuery($query);
        $token = $db->loadResult();
        return $token;
    }
    
    /**
     * Method to cancel an edit.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function cancel()
    {
        // Check for request forgeries.
        $this->checkToken();

        // Flush the data from the session.
        $this->app->setUserState('com_automsg.edit.automsg', null);

        // Redirect to user profile.
        $this->setRedirect(Route::_('index.php?option=com_automsg&view=automsg&layout=cancel', false));
    }
}
