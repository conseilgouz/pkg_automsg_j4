<?php
/**
 * Automsg Component  - Joomla 4.x/5.x Component 
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/
namespace ConseilGouz\Component\Automsg\Site\Model;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\Component\Users\Administrator\Model\UserModel;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;


// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Profile model class for Users.
 *
 * @since  1.6
 */
class AutomsgModel extends FormModel
{
    /**
     * @var     object  The user profile data.
     * @since   1.6
     */
    protected $data;

    /**
     * Constructor.
     *
     * @param   array                 $config       An array of configuration options (name, state, dbo, table_path, ignore_request).
     * @param   MVCFactoryInterface   $factory      The factory.
     * @param   FormFactoryInterface  $formFactory  The form factory.
     *
     * @see     \Joomla\CMS\MVC\Model\BaseDatabaseModel
     * @since   3.2
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
    {
        $config = array_merge(
            [
                'events_map' => ['validate' => 'user']
            ],
            $config
        );

        parent::__construct($config, $factory, $formFactory);
    }

    /**
     * Method to get the profile form data.
     *
     * The base form data is loaded and then an event is fired
     * for users plugins to extend the data.
     *
     * @return  User
     *
     * @since   1.6
     * @throws  \Exception
     */
    public function getData()
    {
        if ($this->data === null) {
            $userId = $this->getState('com_automsg.automsg.automsg.id');

            if (!$userId) {
                $user = Factory::getApplication()->getIdentity();
                $userId = $user->id;
            }
            // Initialise the table with Joomla\CMS\User\User.
            $this->data = new User($userId);

            // Override the base user data with any data in the session.
            $temp = (array) Factory::getApplication()->getUserState('com_automsg.edit.automsg.data', []);
            $temp['id'] = $userId;
            foreach ($temp as $k => $v) {
                $this->data->$k = $v;
            }

            $registry           = new Registry($this->data->params);
            $this->data->params = $registry->toArray();
        }

        return $this->data;
    }

    /**
     * Method to get the automsg form.
     *
     * The base form is loaded from XML and then an event is fired
     * for users plugins to extend the form with extra fields.
     *
     * @param   array    $data      An optional array of data for the form to interrogate.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|bool  A Form object on success, false on failure
     *
     * @since   1.6
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_automsg.automsg', 'automsg', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.6
     */
    protected function loadFormData()
    {
        $data = $this->getData();

        $this->preprocessData('com_automsg.automsg', $data, 'user');

        return $data;
    }

    /**
     * Override preprocessForm to load the user plugin group instead of content.
     *
     * @param   Form    $form   A Form object.
     * @param   mixed   $data   The data expected for the form.
     * @param   string  $group  The name of the plugin group to import (defaults to "content").
     *
     * @return  void
     *
     * @throws  \Exception if there is an error in the form event.
     *
     * @since   1.6
     */
    protected function preprocessForm(Form $form, $data, $group = 'user')
    {
        parent::preprocessForm($form, $data, $group);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return  void
     *
     * @since   1.6
     * @throws  \Exception
     */
    protected function populateState()
    {
        // Get the application object.
        $params = Factory::getApplication()->getParams('com_automsg');
        $input = Factory::getApplication()->input;
        $token = $input->getRaw('token');
        if (!$token) {
            return false;
        }
        $userId = $this->getIdFromToken($token);
        if (!$userId) {
            $this->setError(Text::_('COM_AUTOMSG_USER_NOT_FOUND'));
        }
        $this->setState('com_automsg.automsg.automsg.id', $userId);
        // Load the parameters.
        $this->setState('params', $params);
    }
    protected function getIdFromToken($token) {
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('user_id'))
			->from($db->quoteName('#__user_profiles'))
			->where($db->quoteName('profile_key') . ' like ' .$db->quote('profile_automsg.token').' AND '.$db->quoteName('profile_value'). ' = '.$db->quote($token));
		$db->setQuery($query);
		$user = $db->loadResult();
		return $user;
    }
    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  mixed  The user id on success, false on failure.
     *
     * @since   1.6
     * @throws  \Exception
     */
    public function save($data)
    {
        $userId = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('automsg.id');

        if (!$userId) return false; 
        $user = new User($userId);
        // Bind the data.
        if (!$user->bind($data)) {
            $this->setError($user->getError());

            return false;
        }
        // Load the users plugin group.
        PluginHelper::importPlugin('user');
        // automsg parameter is updated in plugin
        $prop = $user->getProperties();
        $result = Factory::getApplication()->triggerEvent('onUserAfterSave', [$prop, false, true, $this->getError()]);
        return $user->id;
    }

}
