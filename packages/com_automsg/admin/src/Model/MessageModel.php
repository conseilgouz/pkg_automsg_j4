<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * Version			: 4.0.0
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

class MessageModel extends AdminModel
{
    protected function preprocessForm(Form $form, $data, $group = 'content')
    {
        parent::preprocessForm($form, $data, $group);
    }
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_automsg.message', 'message', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            return false;
        }

        return $form;
    }
    public function getTable($type = 'Message', $prefix = 'Administrator', $config = [])
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
        $data = Factory::getApplication()->getUserState('com_automsg.edit.message.data', array());
        if (empty($data)) {
            $data = $this->getItem();
        }
        // split general parameters
        return $data;
    }
    public function getMessagesList($data)
    {
        $db		= $this->getDatabase();
        $query	= $db->getQuery(true);

        // Select the required fields from the table.
        $query->select('sent, state,cr, GROUP_CONCAT(DISTINCT id) as ids, GROUP_CONCAT(DISTINCT article_id) as articles');
        $query->from('#__automsg');
        if (!$data->sent) {
            $query->where($db->quoteName('sent').' IS NULL ');
        } else{
            $query->where($db->quoteName('sent').' = '.$db->quote($data->sent));
        }
        $query->group('sent,state,cr');
        $db->setQuery($query);
        return $db->loadObjectList();
    }
    public function getMessageErrors($sent) {
        $db		= $this->getDatabase();
        $query	= $db->getQuery(true);

        // Select the required fields from the table.
        $query->select('userid, error');
        $query->from('#__automsg_errors');
        $query->where($db->qn('created').' = '.$db->q($sent));
        $db->setQuery($query);
        return $db->loadObjectList();
    }

}
