<?php
/** Automsg Task
* Version			: 1.2.0
* copyright 		: Copyright (C) 2024 ConseilGouz. All rights reserved.
* license    		: https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
*
*/

namespace ConseilGouz\Plugin\Task\AutoMsg\Extension;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use ConseilGouz\Automsg\Helper\Automsg as AutomsgHelper;

final class AutoMsg extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
    use TaskPluginTrait;

    /**
     * @var boolean
     * @since 4.1.0
     */
    protected $autoloadLanguage = true;
    /**
     * @var string[]
     *
     * @since 4.1.0
     */
    protected const TASKS_MAP = [
        'automsg' => [
            'langConstPrefix' => 'PLG_TASK_AUTOMSG',
            'form'            => 'automsg',
            'method'          => 'automsg',
        ],
    ];
    protected $autoparams;
    protected $tokens;
    protected $articles;
    /**
     * @inheritDoc
     *
     * @return string[]
     *
     * @since 4.1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList'    => 'advertiseRoutines',
            'onExecuteTask'        => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    protected function automsg(ExecuteTaskEvent $event): int
    {
        $this->autoparams = AutomsgHelper::getParams();
        $this->goMsg();
        return TaskStatus::OK;
    }
    private function goMsg()
    {
        $lang = Factory::getApplication()->getLanguage();
        $lang->load('com_automsg');
        // get users
        $users = AutomsgHelper::getUsers($this->autoparams->usergroups);
        // check profile automsg
        $deny = AutomsgHelper::getDenyUsers();

        $users = array_diff($users, $deny);
        if (empty($users)) { // no user left => exit
            return true;
        }
        $this->tokens = AutomsgHelper::getAutomsgToken($users);

        $this->articles = $this->getArticlesToSend();
        $model     = AutomsgHelper::prepare_content_model();
        if ($this->autoparams->async == 1) {// all articles in one email per user
            // article lines
            $data = [];
            //  prepa model articles
            foreach ($this->articles as $articleid) {
                $article = $model->getItem($articleid);
                $data[] = AutomsgHelper::oneLine($article, $users, $deny);
            }
            if (count($data)) {
                $result = $this->sendEmails($data, $users);
                $state = 1; // assume ok
                if (isset($results['error']) && ($results['error'] > 0)) {
                    $state = 9; // contains error
                }
                AutomsgHelper::updateAutoMsgTable(null, $state);
            }
        } else { // one article per email per user
            foreach ($this->articles as $articleid) {
                $article = $model->getItem($articleid);
                $date = Factory::getDate();
                AutomsgHelper::sendEmails($article, $users, $this->tokens, $deny);
                AutomsgHelper::updateAutoMsgTable($articleid);
            }
        }
    }

    private function getArticlesToSend()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
        ->select('DISTINCT '.$db->quoteName('article_id'))
            ->from($db->quoteName('#__automsg'))
            ->where($db->quoteName('state') . ' = 0');

        $db->setQuery($query);
        $result = $db->loadColumn();
        return $result;
    }

    private function sendEmails($articles, $users)
    {
        $app = Factory::getApplication();

        if ($this->autoparams->log) { // need to log msgs
            AutomsgHelper::createLog();
        }
        $lang = $app->getLanguage();
        $lang->load('com_automsg');

        $results = [];
        $results['total'] = 0;
        $results['sent'] = 0;
        $results['error'] = 0;

        foreach ($users as $user_id) {
            $results['total']++;
            $receiver = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id);
            $go = false;
            $unsubscribe = "";
            if ($this->tokens[$user_id]) {
                $unsubscribe = "<a href='".URI::root()."index.php?option=com_automsg&view=automsg&layout=edit&token=".$this->tokens[$user_id]."' target='_blank'>".Text::_('COM_AUTOMSG_UNSUBSCRIBE')."</a>";
            }
            $data = ['unsubscribe'   => $unsubscribe];
            $mailer = new MailTemplate('com_automsg.asyncmail', $receiver->getParam('language', $app->get('language')));
            $data_articles = ['articles' => $articles];
            $mailer->addTemplateData($data);
            $mailer->addTemplateData($data_articles);
            $mailer->addRecipient($receiver->email, $receiver->name);

            try {
                $send = $mailer->Send();
            } catch (\Exception $e) {
                if ($this->autoparams->log) { // need to log msgs
                    Log::add('Task : Erreur ----> Articles : '.$articles.' non envoyé à '.$receiver->email.'/'.$e->getMessage(), Log::ERROR, 'com_automsg');
                } else {
                    $app->enqueueMessage($e->getMessage().'/'.$receiver->email, 'error');
                }
                AutomsgHelper::store_automsg_error($user_id, $this->articles, $e->getMessage());
                $results['error']++;
                continue; // try next one
            }
            if ($this->autoparams->log == 2) { // need to log msgs
                Log::add('Task : Article OK : '.$articles.' envoyé à '.$receiver->email, Log::DEBUG, 'com_automsg');
            }
            $results['sent']++;
        }
        return $results;
    }
}
