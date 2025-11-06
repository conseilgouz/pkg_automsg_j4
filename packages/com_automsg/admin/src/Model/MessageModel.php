<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x/6.x
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace ConseilGouz\Component\Automsg\Administrator\Model;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use ConseilGouz\Automsg\Helper\Automsg as AutomsgHelper;

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
        $query	= $db->createQuery();

        // Select the required fields from the table.
        $query->select('sent, state,cr, GROUP_CONCAT(DISTINCT id) as ids, GROUP_CONCAT(DISTINCT article_id) as articles');
        $query->from('#__automsg');
        if (!$data->sent) {
            $query->where($db->quoteName('sent').' IS NULL ');
        } else {
            $query->where($db->quoteName('sent').' = '.$db->quote($data->sent));
        }
        $query->group('sent,state,cr');
        $db->setQuery($query);
        return $db->loadObjectList();
    }
    public function getMessageErrors($sent)
    {
        $db		= $this->getDatabase();
        $query	= $db->createQuery();

        // Select the required fields from the table.
        $query->select('*');
        $query->from('#__automsg_errors');
        $query->where($db->qn('timestamp').' = '.$db->q($sent));
        $query->where($db->qn('state').' = 0 ');
        $db->setQuery($query);
        return $db->loadObjectList();
    }
    public function getMessageWaiting($sent)
    {
        $db		= $this->getDatabase();
        $query	= $db->createQuery();

        // Select the required fields from the table.
        $query->select('userid');
        $query->from('#__automsg_waiting');
        $query->where($db->qn('timestamp').' = '.$db->q($sent));
        $query->where($db->qn('state').' = 0');
        $db->setQuery($query);
        return $db->loadObjectList();
    }
    public function retry($pks)
    {
        $autoparams     = AutomsgHelper::getParams();
        $articlemodel   = AutomsgHelper::prepare_content_model();

        $table          = $this->getTable('Error');
        $errors         = $table->get_errors($pks);

        foreach ($errors as $error) {
            $users = [];
            $articles = [];
            $date = Factory::getDate($error->timestamp);
            if (!in_array($error->userid, $users)) {
                $users[] = $error->userid;
            }
            $articleids = trim($error->articleids, '[]');
            $articles   = explode(',', $articleids);
            $tokens     = AutomsgHelper::getAutomsgToken($users);

            if ($autoparams->async == 1) {// one message per user (multiple articles)
                $article_titles = ""; // for reports
                $data           = []; // all articles, one per line
                foreach ($articles as $articleid) {
                    $article = $articlemodel->getItem($articleid);
                    // for report
                    $article_titles .= ($article_titles) ? ',' : '' ;
                    $article_titles .= $article->title;
                    $data[]  = AutomsgHelper::oneLine($article, $users, []);
                }
                if (count($data)) {
                    $results = AutomsgHelper::sendTaskEmails($articles, $data, $users, $tokens, $date);
                    if ($results['error'] == 0) { // error fixed ?
                        $table->updateState($error->id, 1);
                        AutomsgHelper::updateAutoMsgErrorCr($date);
                    }
                }
            } else {
                foreach ($articles as $articleid) {
                    $article = $articlemodel->getItem($articleid);
                    $results = AutomsgHelper::sendEmails($article, $users, $tokens, [], $date);
                    if ($results['error'] == 0) { // error fixed ?
                        $table->updateState($error->id, 1);
                        AutomsgHelper::updateAutoMsgErrorCr($date);
                    }
                }
            }
        }
    }
}
