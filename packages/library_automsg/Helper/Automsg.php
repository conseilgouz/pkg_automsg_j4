<?php
/**
 * @package    AutoMsg
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (C) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 * @license    GNU/GPLv3
 */
// No direct access.

namespace ConseilGouz\Automsg\Helper;

defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Model\ArticleModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use ConseilGouz\Component\Automsg\Administrator\Model\ConfigModel;

class Automsg
{
    //
    // send single article emails
    //
    public static function sendEmails($article, $users, $tokens, $deny, $datesent, $bwaiting = false, $waitingtimestp = [])
    {
        $autoparams = self::getParams();
        $app = Factory::getApplication();
        $lang = $app->getLanguage();
        $lang->load('com_automsg');

        $results = [];
        $results['total'] = 0;
        $results['sent'] = 0;
        $results['error'] = 0;
        $results['waiting'] = 0;

        $msgcreator = $autoparams->msgcreator;
        $libdateformat = "d/M/Y h:m";
        if ($autoparams->log) { // need to log msgs
            self::createLog();
        }
        $creatorId = $article->created_by;
        if (!in_array($creatorId, $users) && (!in_array($creatorId, $deny))) { // creator not in users array : add it
            if (!$bwaiting) {
                $users[] = $creatorId;
            }
        }
        $userFactory = Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class);
        $creator = $userFactory->loadUserById($creatorId);
        $url = "<a href='".URI::root()."index.php?option=com_content&view=article&id=".$article->id."' target='_blank'>".Text::_('COM_AUTOMSG_CLICK')."</a>";
        $info_cat = self::getCategoryName($article->catid);
        $cat_params = json_decode($info_cat[0]->params);
        $cat_img = "";
        if (isset($cat_params->image) && ($cat_params->image != "")) {
            $img = HTMLHelper::cleanImageURL($cat_params->image);
            $cat_img = '<img src="'.URI::root().pathinfo($img->url)['dirname'].'/'.pathinfo($img->url)['basename'].'" />';
        }
        $images  = json_decode($article->images);
        $article->introimg = "";
        if (!empty($images->image_intro)) { // into img exists
            $img = HTMLHelper::cleanImageURL($images->image_intro);
            $article->introimg = '<img src="'.URI::root().pathinfo($img->url)['dirname'].'/'.pathinfo($img->url)['basename'].'" />';
        }
        $article_tags = self::getArticleTags($article->id);
        $itemtags = "";
        foreach ($article_tags as $tag) {
            $itemtags .= '<span class="iso_tag_'.$tag->alias.'">'.(($itemtags == "") ? $tag->tag : "<span class='iso_tagsep'><span>-</span></span>".$tag->tag).'</span>';
        }
        $data = [
            'creator'   => $creator->name,
            'id'        => $article->id,
            'title'     => $article->title,
            'cat'       => $info_cat[0]->title,
            'date'      => HTMLHelper::_('date', $article->created, $libdateformat),
            'intro'     => $article->introtext,
            'catimg'    => $cat_img,
            'url'       => $url,
            'introimg'  => $article->introimg,
            'subtitle'  => '', // not used
            'tags'      => $itemtags,
            'featured'  => $article->featured,
        ];
        foreach ($users as $user_id) {
            $results['total']++;
            if ($autoparams->limit && ($results['total'] > $autoparams->maillimit)) {
                self::store_automsg_waiting($user_id, $article->id, 0, $datesent);
                $results['waiting']++;
                continue;
            }
            $receiver = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id);
            $unsubscribe = "";
            if ($tokens[$user_id]) {
                $unsubscribe = "<a href='".URI::root()."index.php?option=com_automsg&view=automsg&layout=edit&token=".$tokens[$user_id]."' target='_blank'>".Text::_('COM_AUTOMSG_UNSUBSCRIBE')."</a>";
            }
            $data['unsubscribe']    = $unsubscribe;
            $data['sitename']       = str_replace(['@', '|'], '', $app->get('sitename'));
            // Collect data for mail
            if (($user_id == $creatorId) && ($msgcreator == 1)) { // mail specifique au createur de l'article
                $mailer = new MailTemplate('com_automsg.ownermail', $receiver->getParam('language', $app->get('language')));
            } else {
                $mailer = new MailTemplate('com_automsg.usermail', $receiver->getParam('language', $app->get('language')));
            }
            $mailer->addTemplateData($data);
            $mailer->addRecipient($receiver->email, $receiver->name);

            try {
                $res = $mailer->send();
            } catch (\Exception $e) {
                if ($autoparams->log) { // need to log msgs
                    Log::add('Erreur ----> Article : '.$article->title.' non envoyé à '.$receiver->email.'/'.$e->getMessage(), Log::ERROR, 'com_automsg');
                } else {
                    $app->enqueueMessage($e->getMessage().'/'.$receiver->email, 'error');
                }
                self::store_automsg_error($user_id, $article->id, $e->getMessage(), 0, $datesent);
                $results['error']++;
                if ($bwaiting) {
                    $timestamp =  Factory::getDate($waitingtimestp[$article->id]);
                    $waitingcr = ['error' => 1,'sent' => 0];
                    self::updateAutoMsgCr($timestamp, $waitingcr);
                }
                continue;
            }
            $results['sent']++;
            if ($bwaiting) {
                $timestamp =  Factory::getDate($waitingtimestp[$article->id]);
                $waitingcr = ['error' => 0,'sent' => 1];
                self::updateAutoMsgCr($timestamp, $waitingcr);
            }
            if ($autoparams->log == 2) { // need to log all msgs
                Log::add('Article OK : '.$article->title.' envoyé à '.$receiver->email, Log::DEBUG, 'com_automsg');
            }
        }
        return $results;
    }
    //
    // Async task : send emails
    //
    public static function sendTaskEmails($articleids, $articles, $users, $tokens, $datesent, $bwaiting = false, $waitingtimestp = [])
    {
        $autoparams = self::getParams();
        $app = Factory::getApplication();
        if ($autoparams->log) { // need to log msgs
            self::createLog();
        }
        $lang = $app->getLanguage();
        $lang->load('com_automsg');

        $results = [];
        $results['total'] = 0;
        $results['sent'] = 0;
        $results['error'] = 0;
        $results['waiting'] = 0;
        foreach ($users as $user_id) {
            $results['total']++;
            if ($autoparams->limit && ($results['total'] > $autoparams->maillimit)) {
                self::store_automsg_waiting($user_id, $articleids, 0, $datesent);
                $results['waiting']++;
                continue;
            }
            $receiver = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id);
            $go = false;
            $unsubscribe = "";
            if ($tokens[$user_id]) {
                $unsubscribe = "<a href='".URI::root()."index.php?option=com_automsg&view=automsg&layout=edit&token=".$tokens[$user_id]."' target='_blank'>".Text::_('COM_AUTOMSG_UNSUBSCRIBE')."</a>";
            }
            $data = ['unsubscribe'   => $unsubscribe,'sitename' =>  str_replace(['@', '|'], '', $app->get('sitename'))];
            $mailer = new MailTemplate('com_automsg.asyncmail', $receiver->getParam('language', $app->get('language')));
            $data_articles = ['articles' => $articles];
            $mailer->addTemplateData($data);
            $mailer->addTemplateData($data_articles);
            $mailer->addRecipient($receiver->email, $receiver->name);

            try {
                $send = $mailer->Send();
            } catch (\Exception $e) {
                if ($autoparams->log) { // need to log msgs
                    Log::add('Task : Erreur ----> Articles : '.$articleids.' non envoyé à '.$receiver->email.'/'.$e->getMessage(), Log::ERROR, 'com_automsg');
                } else {
                    $app->enqueueMessage($e->getMessage().'/'.$receiver->email, 'error');
                }
                self::store_automsg_error($user_id, $articleids, $e->getMessage(), 0, $datesent);
                $results['error']++;
                if ($bwaiting) {
                    $timestamp =  Factory::getDate($waitingtimestp[$articleids[0]]);
                    $waitingcr = ['error' => 1,'sent' => 0];
                    self::updateAutoMsgCr($timestamp, $waitingcr);
                }
                continue; // try next one
            }
            if ($autoparams->log == 2) { // need to log msgs
                Log::add('Task : Articles OK : '.$articleids.' envoyés à '.$receiver->email, Log::DEBUG, 'com_automsg');
            }
            $results['sent']++;
            if ($bwaiting) {
                $timestamp =  Factory::getDate($waitingtimestp[$articleids[0]]);
                $waitingcr = ['error' => 0,'sent' => 1];
                self::updateAutoMsgCr($timestamp, $waitingcr);
            }
        }
        return $results;
    }
    //
    // send report email to admin with sendEmail = 1
    //
    public static function sendReport($title, $cr)
    {

        $autoparams = self::getParams();
        $app = Factory::getApplication();
        if ($autoparams->log) { // need to log msgs
            self::createLog();
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*')
            ->from('#__users ')
            ->where($db->quoteName('block') . ' = 0')
            ->where($db->quoteName('sendEmail') . ' = 1');
        $db->setQuery($query);
        $users = $db->loadObjectList();

        $data = ['article' => $title,
                 'total'   => $cr['total'],
                 'ok'      => $cr['sent'],
                 'error'   => $cr['error'],
                 'waiting' => $cr['waiting'],
                 'sitename' =>  str_replace(['@', '|'], '', $app->get('sitename'))
                 ];

        foreach ($users as $user) {
            $receiver = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user->id);
            $mailer = new MailTemplate('com_automsg.report', $receiver->getParam('language', $app->get('language')));
            $mailer->addTemplateData($data);
            $mailer->addRecipient($receiver->email, $receiver->name);
            try {
                $send = $mailer->Send();
            } catch (\Exception $e) {
                if ($autoparams->log) { // need to log msgs
                    Log::add('Task : Erreur ----> report non envoyé à '.$receiver->email.'/'.$e->getMessage(), Log::ERROR, 'com_automsg');
                } else {
                    $app->enqueueMessage($e->getMessage().'/'.$receiver->email, 'error');
                }
                continue; // try next one
            }
        }
    }
    //
    // get category information
    //
    private static function getCategoryName($id)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*')
            ->from('#__categories ')
            ->where('id = '.(int)$id)
        ;
        $db->setQuery($query);
        return $db->loadObjectList();
    }
    //
    // get article tags
    //
    private static function getArticleTags($id)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('tags.title as tag, tags.alias as alias, tags.note as note, tags.images as images, parent.title as parent_title, parent.alias as parent_alias')
            ->from('#__contentitem_tag_map as map ')
            ->innerJoin('#__content as c on c.id = map.content_item_id')
            ->innerJoin('#__tags as tags on tags.id = map.tag_id')
            ->innerJoin('#__tags as parent on parent.id = tags.parent_id')
            ->where('c.id = '.(int)$id.' AND map.type_alias like "com_content%"')
        ;
        $db->setQuery($query);
        return $db->loadObjectList();
    }
    //
    // get all users from defined user groups
    //
    public static function getUsers($usergroups)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('DISTINCT '.$db->quoteName('u.id'))
            ->from($db->quoteName('#__users').' as u ')
            ->join('LEFT', $db->quoteName('#__user_usergroup_map').' as g on u.id = g.user_id')
            ->where($db->quoteName('block') . ' = 0 AND g.group_id IN ('.$usergroups.')');
        $db->setQuery($query);
        return (array) $db->loadColumn();
    }
    //
    // Get deny users list
    //
    public static function getDenyUsers()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('p.user_id'))
            ->from($db->quoteName('#__user_profiles').' as p ')
            ->where($db->quoteName('profile_key') . ' like ' .$db->quote('profile_automsg.%').' AND '.$db->quoteName('profile_value'). ' like '.$db->quote('%Non%'));
        $db->setQuery($query);
        return (array) $db->loadColumn();
    }
    //
    // Get all users token (used in unsubscribe links)
    //
    public static function getAutomsgToken($users)
    {
        $tokens = array();
        foreach ($users as $user) {
            $token = self::checkautomsgtoken($user);
            if ($token) {// token found
                $tokens[$user] = $token;
            }
        }
        return $tokens;
    }
    // check if automsg token exists.
    // if it does not, create it
    //
    private static function checkautomsgtoken($userId)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                 ->select(
                     [
                            $db->quoteName('profile_value'),
                        ]
                 )
                ->from($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' = :userid')
                ->where($db->quoteName('profile_key') . ' LIKE '.$db->quote('profile_automsg.token'))
                ->bind(':userid', $userId, ParameterType::INTEGER);

        $db->setQuery($query);
        $result = $db->loadResult();
        if ($result) {
            return $result;
        } // automsg token already exists => exit
        // create a token
        $query = $db->getQuery(true)
                ->insert($db->quoteName('#__user_profiles'));
        $token = mb_strtoupper(strval(bin2hex(openssl_random_pseudo_bytes(16))));
        $order = 2;
        $query->values(
            implode(
                ',',
                $query->bindArray(
                    [
                        $userId,
                        'profile_automsg.token',
                        $token,
                        $order++,
                    ],
                    [
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                        ParameterType::STRING,
                        ParameterType::INTEGER,
                    ]
                )
            )
        );
        $db->setQuery($query);
        $db->execute();
        return $token;
    }
    // ------------------------------------------------
    // Async task : Create one article line to include in the email
    //
    public static function oneLine($article, $users, $deny)
    {
        $lang = Factory::getApplication()->getLanguage();
        $lang->load('com_automsg');

        $libdateformat = "d/M/Y h:m";
        $creatorId = $article->created_by;
        $userFactory = Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class);
        $creator = $userFactory->loadUserById($creatorId);
        $url = "<a href='".URI::root()."index.php?option=com_content&view=article&id=".$article->id."' target='_blank'>".Text::_("COM_AUTOMSG_CLICK")."</a>";
        $info_cat = self::getCategoryName($article->catid);
        $cat_params = json_decode($info_cat[0]->params);
        $cat_img = "";
        if (isset($cat_params->image) && ($cat_params->image != "")) {
            $img = HTMLHelper::cleanImageURL($cat_params->image);
            $cat_img = '<img src="'.URI::root().pathinfo($img->url)['dirname'].'/'.pathinfo($img->url)['basename'].'" />';
        }
        $images  = json_decode($article->images);
        $article->introimg = "";
        if (!empty($images->image_intro)) { // into img exists
            $img = HTMLHelper::cleanImageURL($images->image_intro);
            $article->introimg = '<img src="'.URI::root().pathinfo($img->url)['dirname'].'/'.pathinfo($img->url)['basename'].'" />';
        }
        if (!in_array($creatorId, $users) && (!in_array($creatorId, $deny))) { // creator not in users array : add it
            $users[] = $creatorId;
        }
        $article_tags = self::getArticleTags($article->id);
        $itemtags = "";
        foreach ($article_tags as $tag) {
            $itemtags .= '<span class="iso_tag_'.$tag->alias.'">'.(($itemtags == "") ? $tag->tag : "<span class='iso_tagsep'><span>-</span></span>".$tag->tag).'</span>';
        }
        $data = [
                'creator'   => $creator->name,
                'id'        => $article->id,
                'title'     => $article->title,
                'cat'       => $info_cat[0]->title,
                'catimg'    => $cat_img,
                'date'      => HTMLHelper::_('date', $article->created, $libdateformat),
                'intro'     => $article->introtext,
                'introimg'  => $article->introimg,
                'url'       => $url,
                'subtitle'  => '', // not used
                'tags'      => $itemtags,
                'featured'  => $article->featured,
            ];
        return $data;
    }
    //
    // Async task : check articles list
    //
    public static function getArticlesToSend()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
        ->select('DISTINCT '.$db->quoteName('article_id'))
            ->from($db->quoteName('#__automsg'))
            ->where($db->quoteName('state') . ' = 0');

        $db->setQuery($query);
        $result = $db->loadColumn();
        return $result;
    }
    //
    // Async task : check waiting articles/users
    //
    public static function checkWaitingArticles()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
        ->select('count(id)')
            ->from($db->qn('#__automsg_waiting'))
            ->where($db->qn('state') . ' = 0');
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    //
    // Async task : get waiting articles/users
    //
    public static function getWaitingArticles()
    {
        $autoparams = self::getParams();
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
        ->select('*')
            ->from($db->qn('#__automsg_waiting'))
            ->where($db->qn('state') . ' = 0')
            ->order('timestamp ASC');
        if ($autoparams->limit) {
            $query->setLimit($autoparams->maillimit);
        }
        $db->setQuery($query);
        $result = $db->loadObjectList();
        return $result;
    }
    //
    // Asynchronous process : store article id in automsg table
    // Synchronous process : store errors only
    //
    public static function store_automsg($article, $state = 0, $timestamp = null, $cr = [])
    {
        $autoparams = self::getParams();

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        if ($state == 9) { // lost article => set remaining ones to state 9 : error
            $query->select('id')
            ->from($db->qn('#__automsg'))
            ->where($db->qn('article_id') . ' = '.$article->id)
            ->where($db->qn('state') .'< 9');
            $db->setQuery($query);
            $losts = $db->loadColumn();
            if (sizeOf($losts)) {
                $query = $db->getQuery(true)
                    ->update($db->qn('#__automsg'))
                    ->set($db->qn('state').'= 9')
                    ->where($db->qn('id').' in ('.implode(',', $losts).')');
                $db->setQuery($query);
                $db->execute();
                return;
            }
        }
        $date = Factory::getDate();

        $query = $db->getQuery(true)
        ->insert($db->qn('#__automsg'));
        $query->values(
            implode(
                ',',
                $query->bindArray(
                    [
                        0, // key
                        $state, // state
                        $article->id,
                        $date->toSql(), // date created
                        null, // date modified
                        $timestamp->toSql(), // timestamp
                        json_encode($cr)
                    ],
                    [
                        ParameterType::INTEGER,
                        ParameterType::INTEGER,
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                        ParameterType::NULL,
                        ParameterType::NULL,
                        ParameterType::STRING
                    ]
                )
            )
        );
        $db->setQuery($query);
        $db->execute();
        if ($autoparams->log == 2) { // need to log all msgs
            self::createLog();
            Log::add('Article in automsg : '.$article->title, Log::DEBUG, 'com_automsg');
        }
    }
    //
    //   Async : Store same timestamp to all sent articles in this session
    //
    public static function updateAutoMsgTable($articleid = null, $state = 0, $timestamp = null, $cr = [])
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
        ->update($db->qn('#__automsg'))
        ->set($db->qn('state').'='.$state.','.$db->qn('sent').'='.$db->q($timestamp->toSql()).','.$db->qn('cr').'='.$db->q(json_encode($cr)))
        ->where($db->qn('state') . ' = 0');
        if ($articleid) {
            $query->where($db->qn('article_id').' = '.$db->q($articleid));
        }
        $db->setQuery($query);
        $db->execute();
        return true;
    }
    //
    //   Async : update waiting articles to sent
    //
    public static function updateAutoMsgWaitingTable($ids = [], $state = 1)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->qn('#__automsg_waiting'))
            ->set($db->qn('state').'='.$state)
            ->whereIn($db->qn('id'), $ids);
        $db->setQuery($query);
        $db->execute();
        return true;
    }
    //
    //   Async : after processing waitings, update cr
    //
    public static function updateAutoMsgCr($timestamp, $cr = [])
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
        ->select('cr')
            ->from($db->qn('#__automsg'))
            ->where($db->qn('sent') . ' = '.$db->q($timestamp->toSql()));
        $query->setLimit(1); // all articles from one session have same timestamp
        $db->setQuery($query);
        $oldcr = $db->loadResult();
        if (!$oldcr) { // should not exist, but do it just in case
            return;
        }
        $oldcr = json_decode($oldcr);
        if (!$cr['error']) {
            $oldcr->sent += $cr['sent'];
        }
        $oldcr->error += $cr['error'];
        $oldcr->waiting -= 1;
        if ($oldcr->total < ($oldcr->sent + $oldcr->error)) { // ignore
            // we had remaining waitings
            return;
        }
        $query = $db->getQuery(true)
            ->update($db->qn('#__automsg'))
            ->set($db->qn('cr').'='.$db->q(json_encode($oldcr)))
            ->where($db->qn('sent').' = '.$db->q($timestamp->toSql()));
        $db->setQuery($query);
        $db->execute();
        return true;
    }
    //
    // store errors
    //
    public static function store_automsg_error($userid, $articleids, $error, $state = 0, $timestamp = null)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
        ->select('id,retry')
        ->from($db->qn('#__automsg_errors'))
        ->where($db->qn('userid').'='.$db->q($userid))
        ->where($db->qn('timestamp').'='.$db->q($timestamp->toSql()));
        $db->setQuery($query);
        $result = $db->loadObject();
        $new = true;
        if ($result->id) {
            $new = false;
        }

        if ($new) {
            $query = $db->getQuery(true)
                ->insert($db->qn('#__automsg_errors'));
            $query->values(
                implode(
                    ',',
                    $query->bindArray(
                        [
                        0, // key
                        $state, // state
                        $userid,
                        json_encode($articleids),
                        $error,
                        $timestamp->toSql(), // timestamp
                        null, // date modified
                        0 // retry
                        ],
                        [
                        ParameterType::INTEGER,
                        ParameterType::INTEGER,
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                        ParameterType::STRING,
                        ParameterType::STRING,
                        ParameterType::NULL,
                        ParameterType::INTEGER,
                        ]
                    )
                )
            );
            $db->setQuery($query);
            $db->execute();
        } else { // update retry counter
            $date = HTMLHelper::_('date', 'now', Text::_('DATE_FORMAT_FILTER_DATETIME'));
            $modified = Factory::getDate($date); // same timestamp for everybody in same request
            $query = $db->getQuery(true)
                ->update($db->qn('#__automsg_errors'))
                ->set($db->qn('retry').'='.($result->retry + 1))
                ->set($db->qn('modified'). '='. $db->q($modified->toSql()))
                ->where($db->qn('id').'='.$result->id);
            $db->setQuery($query);
            $db->execute();
        }

    }
    //
    // store errors
    //
    public static function store_automsg_waiting($userid, $articleids, $state = 0, $timestamp = null)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
        ->insert($db->qn('#__automsg_waiting'));
        $query->values(
            implode(
                ',',
                $query->bindArray(
                    [
                        0, // key
                        $state, // state
                        $userid,
                        json_encode($articleids),
                        $timestamp->toSql(), // timestamp
                        null, // date modified
                    ],
                    [
                        ParameterType::INTEGER,
                        ParameterType::INTEGER,
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                        ParameterType::STRING,
                        ParameterType::NULL,
                    ]
                )
            )
        );
        $db->setQuery($query);
        $db->execute();
    }
    //
    //   Update error after fixing one error
    //
    public static function updateAutoMsgError($id)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->qn('#__automsg_errors'))
            ->set($db->qn('state').'= 1')
            ->where($db->qn('id').' = '.$id);
        $db->setQuery($query);
        $db->execute();
        return true;
    }
    //
    //   Update error in CR after fixing one error
    //
    public static function updateAutoMsgErrorCr($timestamp)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
        ->select('cr')
            ->from($db->qn('#__automsg'))
            ->where($db->qn('sent') . ' = '.$db->q($timestamp->toSql()));
        $query->setLimit(1); // all articles from one session have same timestamp
        $db->setQuery($query);
        $oldcr = $db->loadResult();
        if (!$oldcr) {
            return;
        } // should not exist, but do it just in case
        $oldcr = json_decode($oldcr);
        $oldcr->error -= 1;
        $oldcr->sent += 1;
        if ($oldcr->total < ($oldcr->sent + $oldcr->error)) { // ignore
            // we had remaining waitings
            return;
        }
        $query = $db->getQuery(true)
            ->update($db->qn('#__automsg'))
            ->set($db->qn('cr').'='.$db->q(json_encode($oldcr)))
            ->where($db->qn('sent').' = '.$db->q($timestamp->toSql()));
        $db->setQuery($query);
        $db->execute();
        return true;
    }
    // ------------Other functions
    //
    // get automsg params
    //
    public static function getParams()
    {
        $model = new ConfigModel();
        return $model->getItem(1); // only one config record in db
    }
    //
    // create log file
    //
    public static function createLog()
    {
        Log::addLogger(
            array('text_file' => 'com_automsg.log.php'),
            Log::ALL,
            array('com_automsg')
        );
    }
    //
    // Prepare content model to call $model->getItem(articleid)
    //
    public static function prepare_content_model()
    {
        $model     = new ArticleModel(array('ignore_request' => true));
        $model->setState('params', ComponentHelper::getParams('com_content'));
        $model->setState('list.start', 0);
        $model->setState('list.limit', 1);
        $model->setState('filter.published', 1);
        $model->setState('filter.featured', 'show');
        $access = ComponentHelper::getParams('com_content')->get('show_noauth');
        $model->setState('filter.access', $access);
        $model->setState('list.ordering', 'a.hits');
        $model->setState('list.direction', 'DESC');
        return $model;
    }
    //
    // some waiting messages :
    // - save automsg task execution_rules & cron_rules
    // - put autoparams values & update next_execution
    //
    public static function task_next_exec()
    {
        $autoparams = self::getParams();
        if ($autoparams->save_execution_rules || $autoparams->save_cron_rules) {
            // already saved
            return;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->qn('#__scheduler_tasks'))
            ->where($db->qn('type') . ' = '.$db->q('automsg'))      // automsg task
            ->where($db->quoteName('state') . '= 1');               // enabled
        $db->setQuery($query);
        $task = $db->loadObject();
        if (!$task) {// not defined
            return;
        }
        // save user values
        $query = $db->getQuery(true)
        ->update($db->qn('#__automsg_config'))
        ->set($db->qn('save_execution_rules').'='.$db->q($task->execution_rules))
        ->set($db->qn('save_cron_rules').'='.$db->q($task->cron_rules))
        ->where($db->qn('id') . ' = 1');
        $db->setQuery($query);
        $db->execute();
        // compute automsg params to use in the task
        $execution_rules = ["rule-type" => "interval-hours",
                            "interval-hours" => $autoparams->maildelay,
                            "exec-day" => "1",
                            "exec-time" => "00:01"
                            ];
        $cron_rules      = ["type" => "interval",
                            "exp" => "PT".$autoparams->maildelay."H"
                            ];
        $lastExec = Factory::getDate('now');
        $interval = new \DateInterval('PT'.$autoparams->maildelay.'H');
        $nextExec = $lastExec->add($interval);
        $nextExec = $nextExec->toSql();
        // update task
        $query = $db->getQuery(true)
        ->update($db->qn('#__scheduler_tasks'))
        ->set($db->qn('next_execution').'='.$db->q($nextExec))
        ->set($db->qn('execution_rules').'='.$db->q(json_encode($execution_rules)))
        ->set($db->qn('cron_rules').'='.$db->q(json_encode($cron_rules)))
        ->where($db->qn('type') . ' = '.$db->q('automsg'))      // automsg task
        ->where($db->quoteName('state') . '= 1');               // enabled
        $db->setQuery($query);
        $db->execute();
    }
    // no more waiting messages :
    // - restore execution_rules & cron_rules from automsg_config table
    // - empty automsg_config infos
    //
    public static function task_restore_exec()
    {
        $autoparams = self::getParams();
        if (!$autoparams->save_execution_rules && !$autoparams->save_cron_rules) {
            // already empty
            return;
        }
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        // restore saved values
        $query = $db->getQuery(true)
        ->update($db->qn('#__scheduler_tasks'))
        ->set($db->qn('execution_rules').'='.$db->q($autoparams->save_execution_rules))
        ->set($db->qn('cron_rules').'='.$db->q($autoparams->save_cron_rules))
        ->where($db->qn('type') . ' = '.$db->q('automsg'))      // automsg task
        ->where($db->quoteName('state') . '= 1');               // enabled
        $db->setQuery($query);
        $db->execute();
        // update task
        $query = $db->getQuery(true)
        ->update($db->qn('#__automsg_config'))
        ->set($db->qn('save_cron_rules').'= null')
        ->set($db->qn('save_execution_rules').'= null')
        ->where($db->quoteName('id') . '= 1');
        $db->setQuery($query);
        $db->execute();
    }
    // Lost article
    public static function lost_article($articleid, $timestamp)
    {
        $autoparams = self::getParams();
        self::store_automsg_error(0, [$articleid], sprintf(Text::_('COM_AUTOMSG_NOT_FOUND'), $articleid), 9, $timestamp);
        $article = new \StdClass();
        $article->id = $articleid;
        $article->title = sprintf(Text::_('COM_AUTOMSG_NOT_FOUND'), $articleid);
        self::store_automsg($article, 9, $timestamp);
        $results = [];
        $results['total'] = 0;
        $results['sent'] = 0;
        $results['error'] = 0;
        $results['waiting'] = 0;
        if ($autoparams->report) {
            self::sendReport(sprintf(Text::_('COM_AUTOMSG_NOT_FOUND'), $articleid), $results);
        }
    }
    // check task status and returns it to administrator messages page
    public static function getTaskStatus() {
        $autoparams = self::getParams();
        if ( !$autoparams->async && !$autoparams->limit ) return '';
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->qn('#__scheduler_tasks'))
            ->where($db->qn('type') . ' = '.$db->q('automsg'))  // automsg task
            ->where($db->quoteName('state') . '>= 0');          // enabled
        $db->setQuery($query);
        $task = $db->loadObject();
        if (!$task) { 
            return "<span class='text-danger'>".Text::_('COM_AUTOMSG_TASK_NOTASK')."</span>";
        }
        if ($task->state == 0) {
            return "<span class='text-danger'>".Text::_('COM_AUTOMSG_TASK_DISABLED')."</span>";
        }
        if ($task->locked) {
            return "<span class='text-danger'>".sprintf(Text::_('COM_AUTOMSG_TASK_LOCKED'),HTMLHelper::_('date', $task->locked, Text::_('DATE_FORMAT_FILTER_DATETIME')))."</span>";
        }
        return "<span class='text-success'>".sprintf(Text::_('COM_AUTOMSG_TASK_NEXT'),HTMLHelper::_('date', $task->next_execution, Text::_('DATE_FORMAT_FILTER_DATETIME')))."</span>";
    }
}
