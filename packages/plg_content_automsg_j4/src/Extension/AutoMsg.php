<?php
/**
 * Plugin AutoMsg : send Email to selected users when an article is published
 * Version		  : 4.0.0
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 */

namespace ConseilGouz\Plugin\Content\AutoMsg\Extension;

defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseAwareTrait;
use ConseilGouz\Automsg\Helper\Automsg as AutomsgHelper;

final class AutoMsg extends CMSPlugin
{
    use DatabaseAwareTrait;

    protected $autoparams;

    public function onContentAfterSave($context, $article, $isNew): void
    {
        try {
            $this->autoparams = AutomsgHelper::getParams();
        } catch (\Exception $e) { // ignore errors
            return;
        }
        $auto = $this->autoparams->msgauto;
        // Check if this function is enabled.
        if (!$auto) {
            return ;
        }
        // Check this is a new article.
        if (!$isNew) {
            return ;
        }
        if (($article->state == 1) && ($auto == 1)) {// article auto publié
            $arr[0] = $article->id;
            self::onContentChangeState($context, $arr, $article->state);
            return;
        }
        return ;
    }
    /**
     * Change the state in core_content if the state in a table is changed
     *
     * @param   string   $context  The context for the content passed to the plugin.
     * @param   array    $pks      A list of primary key ids of the content that has changed state.
     * @param   integer  $value    The value of the state that the content has been changed to.
     *
     * @return  boolean
     *
     * @since   3.1
     */
    public function onContentChangeState($context, $pks, $value)
    {
        if (($context != 'com_content.article') && ($context != 'com_content.form')) {
            return true;
        }
        if ($value == 0) { // unpublish => on sort
            return true;
        }
        // parametres du plugin
        $this->autoparams = AutomsgHelper::getParams();
        $categories = [];
        if ($this->autoparams->categories) {
            $categories = explode(',', $this->autoparams->categories);
        }
        // prepa model articles
        $model     = AutomsgHelper::prepare_content_model();
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
        $timestamp = Factory::getDate($date); // same timestamp for everybody in same request

        foreach ($pks as $articleid) {
            $article = $model->getItem($articleid);
            if (!empty($categories) && !in_array($article->catid, $categories)) {
                continue; // wrong category
            }
            $async = false;
            if (PluginHelper::isEnabled('task', 'automsg') && ComponentHelper::isEnabled('com_automsg')) {
                $async = true; // automsg task plugin / component ok
            }
            if (($this->autoparams->async > 0) && $async) {
                AutomsgHelper::store_automsg($article, 0, $timestamp);
            } else {
                $results = AutomsgHelper::sendEmails($article, $users, $tokens, $deny, $timestamp);
                $state = 1; // assume ok
                if (isset($results['error']) && ($results['error'] > 0)) {
                    $state = 9; // contains error
                }
                AutomsgHelper::store_automsg($article, $state, $timestamp, $results);
                if ($this->autoparams->report) {
                    AutomsgHelper::sendReport($article->title, $results);
                }
                if (isset($results['waiting']) && ($results['waiting'] > 0)) {
                    // some waiting messages : update task next_execution
                    AutomsgHelper::task_next_exec();
                }
            }
        }
        return true;
    }
}
