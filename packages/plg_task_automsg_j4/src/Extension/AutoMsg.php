<?php
/** Automsg Task
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
*/

namespace ConseilGouz\Plugin\Task\AutoMsg\Extension;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
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
        ]
    ];
    protected $autoparams;
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
            'onTaskExecuteSuccess' => 'onTaskExecuteSuccess'
        ];
    }

    protected function automsg(ExecuteTaskEvent $event): int
    {
        $this->autoparams = AutomsgHelper::getParams();
        $params = $event->getArgument('params');
        $manual = false;
        if (isset($params->manual)) {
            $manual = $params->manual;
        }
        $this->goMsg($manual);
        return TaskStatus::OK;
    }
    private function goMsg($manual = false)
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
        $tokens = AutomsgHelper::getAutomsgToken($users);

        $date = HTMLHelper::_('date', 'now', Text::_('DATE_FORMAT_FILTER_DATETIME'));
        $date = Factory::getDate($date); // same timestamp for everybody in same request

        $articles = AutomsgHelper::getArticlesToSend();
        $b_waiting = false;
        $waitingtimestp = [];
        if (!count($articles)) { // no article : check for waiting
            $waitings = AutomsgHelper::getWaitingArticles();
            if (!count($waitings)) {
                return true;
            } // no waiting => exit
            $b_waiting = true;
            $users = [];
            $ids = [];
            foreach ($waitings as $waiting) {
                $date = Factory::getDate($waiting->timestamp);
                if (!in_array($waiting->userid, $users)) {
                    $users[] = $waiting->userid;
                }
                $ids[] = $waiting->id;
                $articleids = trim($waiting->articleids, '[]');
                $articleids = explode(',', $articleids);
                foreach ($articleids as $articleid) {
                    if (!in_array($articleid, $articles)) {
                        $articles[] = $articleid;
                        $waitingtimestp[$articleid] = $waiting->timestamp;
                    }
                }
            }
        }

        $model   = AutomsgHelper::prepare_content_model();
        $results = [];

        if ($this->autoparams->async == 1) {// all articles in one email per user
            // article lines
            $data = [];
            $article_titles = ""; // for reports
            foreach ($articles as $articleid) {
                try {
                    $article = $model->getItem($articleid);
                } catch (\Exception $e) {
                    AutomsgHelper::lost_article($articleid, $date);
                    if ($b_waiting) {
                        AutomsgHelper::updateAutoMsgWaitingTable($ids, 9);
                    }
                    continue;
                }
                // for report
                $article_titles .= ($article_titles) ? ',' : '' ;
                $article_titles .= $article->title;
                //
                $data[]  = AutomsgHelper::oneLine($article, $users, $deny);
            }
            if (count($data)) {
                $results = AutomsgHelper::sendTaskEmails($articles, $data, $users, $tokens, $date, $b_waiting, $waitingtimestp);
                $state   = 1; // assume ok
                if ($b_waiting) {
                    AutomsgHelper::updateAutoMsgWaitingTable($ids);
                } else {
                    AutomsgHelper::updateAutoMsgTable(null, $state, $date, $results);
                }
                if ($this->autoparams->report) {
                    AutomsgHelper::sendReport($article_titles, $results);
                }
            }
        } else { // one article per email per user
            foreach ($articles as $articleid) {
                try {
                    $article = $model->getItem($articleid);
                } catch (\Exception $e) {
                    AutomsgHelper::lost_article($articleid, $date);
                    if ($b_waiting) {
                        AutomsgHelper::updateAutoMsgWaitingTable($ids, 9);
                    }
                    continue;
                }
                $results = AutomsgHelper::sendEmails($article, $users, $tokens, $deny, $date, $b_waiting, $waitingtimestp);
                $state   = 1; // assume ok
                if ($b_waiting) {
                    AutomsgHelper::updateAutoMsgWaitingTable($ids);
                } else {
                    AutomsgHelper::updateAutoMsgTable($articleid, $state, $date, $results);
                }
                if ($this->autoparams->report) {
                    AutomsgHelper::sendReport($article->title, $results);
                }
            }
        }
    }
    //
    // update task rules if necessary
    //
    public function onTaskExecuteSuccess(Event $event)
    {
        if (AutomsgHelper::checkWaitingArticles()) {
            // we have waiting messages : use automsg params
            AutomsgHelper::task_next_exec();
        } else {
            // no more waiting messages : restore task parameters
            AutomsgHelper::task_restore_exec();
        }
    }
}
