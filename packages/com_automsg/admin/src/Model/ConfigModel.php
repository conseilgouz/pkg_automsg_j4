<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x/6.x
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace ConseilGouz\Component\Automsg\Administrator\Model;

defined('_JEXEC') or die;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\Database\DatabaseInterface;

class ConfigModel extends AdminModel
{
    protected function preprocessForm(Form $form, $data, $group = 'content')
    {
        parent::preprocessForm($form, $data, $group);
    }
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_automsg.config', 'config', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            return false;
        }

        return $form;
    }
    public function getTable($type = 'Config', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($type, $prefix, $config);
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return    mixed    The data for the form.
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_automsg.edit.config.data', array());
        if (empty($data)) {
            $data = $this->getItem(1); // only one record in config.
        }
        $data->usergroups = explode(',', $data->usergroups);
        $data->categories = explode(',', $data->categories);
        // split general parameters
        return $data;
    }
    /**
     *  Method to validate form data.
     */
    public function validate($form, $data, $group = null)
    {
        $name = $data['name'];
        unset($data["name"]);

        return array(
            'name'   => $name,
            'params' => json_encode($data)
        );
    }

}
