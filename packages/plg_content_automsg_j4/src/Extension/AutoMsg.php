<?php
/**
 * Plugin AutoMsg : send Email to selected users when an article is published
 * Version		  : 3.2.0
 *
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 */

namespace ConseilGouz\Plugin\Content\AutoMsg\Extension;

defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Content\Site\Model\ArticleModel;
use Joomla\Database\DatabaseAwareTrait;
use ConseilGouz\Automsg\Helper\Automsg as AutomsgHelper;

final class AutoMsg extends CMSPlugin
{
    use DatabaseAwareTrait;

    protected $itemtags;
    protected $info_cat;
    protected $tag_img;
    protected $cat_img;
    protected $url;
    protected $needCatImg;
    protected $needIntroImg;
    protected $autoparams;

    public function onContentAfterSave($context, $article, $isNew): void
    {
        $this->autoparams = AutomsgHelper::getParams();
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
        $usergroups = $this->autoparams->usergroups;

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('u.id'))
            ->from($db->quoteName('#__users').' as u ')
            ->join('LEFT', $db->quoteName('#__user_usergroup_map').' as g on u.id = g.user_id')
            ->where($db->quoteName('block') . ' = 0 AND g.group_id IN ('.$usergroups.')');
        $db->setQuery($query);
        $users = (array) $db->loadColumn();
        // check profile automsg
        $query = $db->getQuery(true)
            ->select($db->quoteName('p.user_id'))
            ->from($db->quoteName('#__user_profiles').' as p ')
            ->where($db->quoteName('profile_key') . ' like ' .$db->quote('profile_automsg.%').' AND '.$db->quoteName('profile_value'). ' like '.$db->quote('%Non%'));
        $db->setQuery($query);
        $deny = (array) $db->loadColumn();
        $users = array_diff($users, $deny);

        if (empty($users)) {
            return true;
        }
        $tokens = AutomsgHelper::getAutomsgToken($users);

        foreach ($pks as $articleid) {
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

            $article = $model->getItem($articleid);
            if (!empty($categories) && !in_array($article->catid, $categories)) {
                continue; // wrong category
            }
            $async = false;
            if (PluginHelper::isEnabled('task', 'automsg') && ComponentHelper::isEnabled('com_automsg')) {
                $async = true; // automsg task plugin / component ok
            }
            if ($this->autoparams->async && $async) {
                AutomsgHelper::async($article);
            } else {
                AutomsgHelper::sendEmails($article, $users, $tokens, $deny);
            }
        }
        return true;
    }


}
