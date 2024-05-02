<?php
/** Automsg Task
* Version			: 1.2.0
* copyright 		: Copyright (C) 2024 ConseilGouz. All rights reserved.
* license    		: https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
*
*/

namespace ConseilGouz\Plugin\Task\AutoMsg\Extension;

defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Model\ArticleModel;
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
    protected $categories;
    protected $usergroups;
    protected $deny;
    protected $tokens;
    protected $itemtags;
    protected $info_cat;
    protected $tag_img;
    protected $cat_img;
    protected $cat_img_emb;
    protected $cat_emb_img;
    protected $introimg;
    protected $introimg_emb;
    protected $url;
    protected $needCatImg;
    protected $needIntroImg;
    protected $creator;
    protected $articles;
    protected $users;
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
        $this->categories = [];
        if ($this->autoparams->categories) {
            $this->categories = explode(',', $this->autoparams->categories);
        }
        $this->usergroups = $this->autoparams->usergroups;
        $this->goMsg();
        return TaskStatus::OK;
    }
    private function goMsg()
    {
        $lang = Factory::getApplication()->getLanguage();
        $lang->load('com_automsg');
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
        ->select($db->quoteName('u.id'))
        ->from($db->quoteName('#__users').' as u ')
        ->join('LEFT', $db->quoteName('#__user_usergroup_map').' as g on u.id = g.user_id')
        ->where($db->quoteName('block') . ' = 0 AND g.group_id IN ('.$this->usergroups.')');
        $db->setQuery($query);
        $this->users = (array) $db->loadColumn();
        // check profile automsg
        $query = $db->getQuery(true)
        ->select($db->quoteName('p.user_id'))
        ->from($db->quoteName('#__user_profiles').' as p ')
        ->where($db->quoteName('profile_key') . ' like ' .$db->quote('profile_automsg.%').' AND '.$db->quoteName('profile_value'). ' like '.$db->quote('%Non%'));
        $db->setQuery($query);
        $this->deny = (array) $db->loadColumn();
        $this->users = array_diff($this->users, $this->deny);

        if (empty($this->users)) {
            return true;
        }
        $this->tokens = AutomsgHelper::getAutomsgToken($this->users);
        $this->articles = $this->getArticlesToSend();
        // build message body
        $data = [];
        $model     = new ArticleModel(array('ignore_request' => true));
        $model->setState('params', $this->params);
        $model->setState('list.start', 0);
        $model->setState('list.limit', 1);
        $model->setState('filter.published', 1);
        $model->setState('filter.featured', 'show');
        // Access filter
        $access = ComponentHelper::getParams('com_content')->get('show_noauth');
        $model->setState('filter.access', $access);

        // Ordering
        $model->setState('list.ordering', 'a.hits');
        $model->setState('list.direction', 'DESC');

        foreach ($this->articles as $articleid) {
            $article = $model->getItem($articleid);
            $data[] = AutomsgHelper::oneLine($article, $this->users, $this->deny);
        }
        if (count($data)) {
            $this->sendEmails($data);
            $this->updateAutoMsgTable();
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
    private function updateAutoMsgTable()
    {
        $db    = $this->getDatabase();
        $date = Factory::getDate();
        $query = $db->getQuery(true)
        ->update($db->quoteName('#__automsg'))
        ->set($db->quoteName('state').'=1,'.$db->quoteName('sent').'='.$db->quote($date->toSql()))
        ->where($db->quoteName('state') . ' = 0');
        $db->setQuery($query);
        $db->execute();
        return true;
    }

    private function sendEmails($articlesList)
    {
        $app = Factory::getApplication();

        if ($this->autoParams->log) { // need to log msgs
            AutomsgHelper::createLog();
        }
        $lang = $app->getLanguage();
        $lang->load('com_automsg');
        foreach ($this->users as $user_id) {
            // Load language for messaging
            $receiver = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id);
            $go = false;
            $unsubscribe = "";
            if ($this->tokens[$user_id]) {
                $unsubscribe = "<a href='".URI::root()."index.php?option=com_automsg&view=automsg&layout=edit&token=".$this->tokens[$user_id]."' target='_blank'>".Text::_('COM_AUTOMSG_UNSUBSCRIBE')."</a>";
            }
            $data = ['unsubscribe'   => $unsubscribe];
            $mailer = new MailTemplate('plg_task_automsg.asyncmail', $receiver->getParam('language', $app->get('language')));
            $articles = ['articles' => $articlesList];
            $mailer->addTemplateData($articles);
            $mailer->addTemplateData($data);
            $mailer->addRecipient($receiver->email, $receiver->name);

            try {
                $send = $mailer->Send();
            } catch (\Exception $e) {
                if ($this->autoParams->log) { // need to log msgs
                    Log::add('Task : Erreur ----> Articles : '.$articlesList.' non envoyé à '.$receiver->email.'/'.$e->getMessage(), Log::ERROR, 'com_automsg');
                } else {
                    $app->enqueueMessage($e->getMessage().'/'.$receiver->email, 'error');
                }
                continue; // try next one
            }
            if ($this->autoParams->log == 2) { // need to log msgs
                Log::add('Task : Article OK : '.$articlesList.' envoyé à '.$receiver->email, Log::DEBUG, 'com_automsg');
            }
        }
    }
}
