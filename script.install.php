<?php
/**
 * @package    AutoMsg
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (C) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 * @license    GNU/GPLv3
 */
// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Version;
use Joomla\Component\Mails\Administrator\Model\TemplateModel;
use Joomla\Component\Scheduler\Administrator\Model\TaskModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

class PlgSystemAutomsgInstallerInstallerScript
{
    private $min_joomla_version     = '4.0.0';
    private $min_php_version        = '8.0';
    private $name                   = 'Automsg';
    private $dir                    = null;
    private $lang                   = null;
    private $previous_version        = "";
    private $installerName          = 'automsginstaller';
    private $extname                 = 'automsg';
    public function __construct()
    {
        $this->dir = __DIR__;
        $this->lang = Factory::getApplication()->getLanguage();

    }
    public function uninstall($parent)
    {
        $template = new TemplateModel(array('ignore_request' => true));
        $table = $template->getTable();
        $table->delete('com_automsg.ownermail');
        $table->delete('com_automsg.usermail');
        $table->delete('com_automsg.asyncmail');
        $table->delete('com_automsg.report');
        return true;
    }
    public function preflight($route, $installer)
    {
        // To prevent installer from running twice if installing multiple extensions
        if (! file_exists($this->dir . '/' . $this->installerName . '.xml')) {
            return true;
        }
        if (! $this->passMinimumJoomlaVersion()) {
            $this->uninstallInstaller();
            return false;
        }

        if (! $this->passMinimumPHPVersion()) {
            $this->uninstallInstaller();
            return false;
        }
        $this->previous_version = null;

        if (file_exists(JPATH_ADMINISTRATOR . '/components/com_automsg/automsg.xml')) {
            $xml = simplexml_load_file(JPATH_ADMINISTRATOR . '/components/com_automsg/automsg.xml');
            $this->previous_version = $xml->version;
        }
        // To prevent XML not found error
        $this->createExtensionRoot();

        return true;
    }

    public function postflight($route, $installer)
    {
        $this->lang->load($this->extname);

        if (! in_array($route, ['install', 'update'])) {
            return true;
        }

        // To prevent installer from running twice if installing multiple extensions
        if (! file_exists($this->dir . '/' . $this->installerName . '.xml')) {
            return true;
        }

        // First install the Library
        if (! $this->installLibrary()) {
            // Uninstall this installer
            $this->uninstallInstaller();

            return false;
        }

        // Then install the rest of the packages
        if (! $this->installPackages()) {
            // Uninstall this installer
            $this->uninstallInstaller();

            return false;
        }
        $this->postInstall();
        Factory::getApplication()->enqueueMessage(Text::_('PLG_AUTOMSG_INSTALL_END'), 'notice');

        // Uninstall this installer
        $this->uninstallInstaller();

        return true;
    }
    private function postInstall()
    {
        // remove obsolete update sites
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->delete('#__update_sites')
            ->where($db->quoteName('location') . ' like "%432473037d.url-de-test.ws/%"');
        $db->setQuery($query);
        $db->execute();

        // get previous versions parameters
        $plugin = PluginHelper::getPlugin('content', 'automsg');
        if ($plugin) { // automsg was defined : get old values
            $params = json_decode($plugin->params);
        }
        if (isset($params->usergroups)) {
            $this->update_com_config($params);
        }
        // create mail template
        $query = $db->getQuery(true);
        $query->select('count(`template_id`)');
        $query->from('#__mail_templates');
        $query->where('extension = ' . $db->quote('plg_content_automsg'));
        $db->setQuery($query);
        $result_content = $db->loadResult();
        $query = $db->getQuery(true);
        $query->select('count(`template_id`)');
        $query->from('#__mail_templates');
        $query->where('extension = ' . $db->quote('plg_task_automsg'));
        $db->setQuery($query);
        $result_task = $db->loadResult();
        $query = $db->getQuery(true);
        $query->select('count(`template_id`)');
        $query->from('#__mail_templates');
        $query->where('template_id = ' . $db->quote('com_automsg.report'));
        $db->setQuery($query);
        $result_report = $db->loadResult();
        if (!$result_task) {
            $this->create_task_mail_template();
        }
        if (!$result_report) {
            $this->create_report_mail_template();
        }
        if (!$result_content) {
            $this->create_mail_templates();
        } else { // plg_content_automsg/plg_task_automsg => com_automsg mail template
            $this->update_mail_templates();
        }
        // check email config : should not be plaintext
        $this->check_email_config();
        // check automsg task
        $this->check_automsg_task();
        // activate all automsg plugins
        $conditions = array(
            $db->qn('type') . ' = ' . $db->q('plugin'),
            $db->qn('element') . ' = ' . $db->quote('automsg')
        );
        $fields = array($db->qn('enabled') . ' = 1');

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
        }

    }
    private function update_com_config($params)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $categories = [];
        if (isset($params->categories)) {
            $categories = $params->categories;
        }
        $users = implode(',', $params->usergroups);
        $categories = implode(',', $categories);
        $query = $db->getQuery(true)
                ->update('#__automsg_config')
                ->set($db->qn('usergroups').' = '.$db->q($users))
                ->set($db->qn('categories').' = '.$db->q($categories))
                ->set($db->qn('msgcreator').' = '.$db->q($params->msgcreator))
                ->set($db->qn('msgauto').' = '.$db->q($params->msgauto))
                ->set($db->qn('async').' = '.$db->q($params->async))
                ->set($db->qn('log').' = '.$db->q($params->log))
                ->set($db->qn('modified').' = '.$db->q(Factory::getDate()->toSql()))
                ->where($db->qn('id').' = 1');
        $db->setQuery($query);
        $db->execute();

        $conditions = array(
            $db->qn('type')    . ' = ' . $db->q('plugin'),
            $db->qn('element') . ' = ' . $db->quote('automsg'),
            $db->qn('folder')  . ' = ' . $db->quote('content'),
        );
        $empty = json_encode([]);
        $fields = array($db->qn('params') . ' = '.$db->q($empty));

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            Factory::getApplication()->enqueueMessage(Text::_('PLG_AUTOMSG_INSTALL_CONFIG_ERROR'), 'error');
            return;
        }
        Factory::getApplication()->enqueueMessage(Text::_('PLG_AUTOMSG_INSTALL_CONFIG_OK'), 'notice');
    }
    private function update_mail_templates()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
                ->update('#__mail_templates')
                ->set($db->qn('template_id').' = REPLACE(template_id,'.$db->q('plg_content_automsg').','.$db->q('com_automsg').')')
                ->set($db->qn('subject').' = REPLACE(subject,'.$db->q('PLG_CONTENT_AUTOMSG_').','.$db->q('COM_AUTOMSG_').')')
                ->set($db->qn('body').' = REPLACE(body,'.$db->q('PLG_CONTENT_AUTOMSG_').','.$db->q('COM_AUTOMSG_').')')
                ->where($db->qn('extension').' = '.$db->q('plg_content_automsg'));
        $db->setQuery($query);
        $db->execute();
        $query = $db->getQuery(true)
                ->update('#__mail_templates')
                ->set($db->qn('template_id').' = REPLACE(template_id,'.$db->q('plg_task_automsg').','.$db->q('com_automsg').')')
                ->set($db->qn('subject').' = REPLACE(subject,'.$db->q('PLG_TASK_AUTOMSG_').','.$db->q('COM_AUTOMSG_').')')
                ->set($db->qn('body').' = REPLACE(body,'.$db->q('PLG_TASK_AUTOMSG_').','.$db->q('COM_AUTOMSG_').')')
                ->where($db->qn('extension').' = '.$db->q('plg_task_automsg'));
        $db->setQuery($query);
        $db->execute();
        $query = $db->getQuery(true)
                ->update('#__mail_templates')
                ->set($db->qn('extension').' = '.$db->q('com_automsg'))
                ->where($db->qn('extension').' IN ('.$db->q('plg_task_automsg').','.$db->q('plg_content_automsg').')');
        $db->setQuery($query);
        $db->execute();
        Factory::getApplication()->enqueueMessage(Text::_('PLG_AUTOMSG_INSTALL_TEMPLATES_OK'), 'notice');

    }
    // create task email templates from scratch
    private function create_task_mail_template()
    {
        $template = new TemplateModel(array('ignore_request' => true));
        $table = $template->getTable();
        $data = [];
        // owner mail template
        $data['template_id'] = 'com_automsg.asyncmail';
        $data['extension'] = 'com_automsg';
        $data['language'] = '';
        $data['subject'] = 'COM_AUTOMSG_PUBLISHED_SUBJECT';
        $data['htmlbody'] = '';
        $data['attachments'] = '';
        $data['params'] = '{"tags": ["sitename","creator", "title", "cat", "intro", "catimg", "url", "introimg", "subtitle", "tags", "date","featured","unsubscribe"]}';
        $data['subject'] = 'COM_AUTOMSG_ASYNC_SUBJECT';
        $data['body'] = 'COM_AUTOMSG_ASYNC_MSG';
        $table->save($data);
    }
    // create task email templates from scratch
    private function create_report_mail_template()
    {
        $template = new TemplateModel(array('ignore_request' => true));
        $table = $template->getTable();
        $data = [];
        $data['extension'] = 'com_automsg';
        $data['language'] = '';
        $data['htmlbody'] = '';
        $data['attachments'] = '';
        $data['params'] = '{"tags": ["sitename", "article", "ok", "error", "waiting", "total"]}';
        $data['template_id'] = 'com_automsg.report';
        $data['subject'] = 'COM_AUTOMSG_REPORT_SUBJECT';
        $data['body'] = 'COM_AUTOMSG_REPORT_MSG';
        $table->save($data);
    }
    // create email templates from scratch or from older plugins definition
    private function create_mail_templates()
    {
        // check if defined in previous version
        $plugin = PluginHelper::getPlugin('content', 'automsg');
        if ($plugin) { // automsg was defined : get old values
            $params = json_decode($plugin->params);
        }
        $template = new TemplateModel(array('ignore_request' => true));
        $table = $template->getTable();
        $data = [];

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('count(`template_id`)');
        $query->from('#__mail_templates');
        $query->where('template_id = ' . $db->quote('com_automsg.ownermail'));
        $db->setQuery($query);
        $result_owner = $db->loadResult();
        if (!$result_owner) {
            // owner mail template
            $data['template_id'] = 'com_automsg.ownermail';
            $data['extension'] = 'com_automsg';
            $data['language'] = '';
            $data['subject'] = 'COM_AUTOMSG_PUBLISHED_SUBJECT';
            $data['body'] = 'COM_AUTOMSG_PUBLISHED_MSG';
            $data['htmlbody'] = '';
            $data['attachments'] = '';
            $data['params'] = '{"tags": ["sitename", "creator", "title", "cat", "intro", "catimg", "url", "introimg", "subtitle", "tags", "date","featured","unsubscribe"]}';
            $table->save($data);
            Factory::getApplication()->enqueueMessage(Text::_('PLG_AUTOMSG_CREATE_OWNER_TEMPLATE_OK'), 'notice');
        }
        $query = $db->getQuery(true);
        $query->select('count(`template_id`)');
        $query->from('#__mail_templates');
        $query->where('template_id = ' . $db->quote('com_automsg.usermail'));
        $db->setQuery($query);
        $result_user = $db->loadResult();
        if (!$result_user) {
            // other users mail template
            $data['template_id'] = 'com_automsg.usermail';
            if ($plugin && isset($params->subject)) {
                $subject = $this->tagstouppercase($params->subject);
                $data['subject'] = $subject;
                $body = $this->tagstouppercase($params->body);
                $data['body'] = $body;
            } else {
                $data['subject'] = 'COM_AUTOMSG_USER_SUBJECT';
                $data['body'] = 'COM_AUTOMSG_USER_MSG';
            }
            $table->save($data);
            Factory::getApplication()->enqueueMessage(Text::_('PLG_AUTOMSG_CREATE_USER_TEMPLATE_OK'), 'notice');
        }
        $query = $db->getQuery(true);
        $query->select('count(`template_id`)');
        $query->from('#__mail_templates');
        $query->where('template_id = ' . $db->quote('com_automsg.report'));
        $db->setQuery($query);
        $result_report = $db->loadResult();
        if (!$result_report) {
            // Report message
            $data['params'] = '{"tags": ["sitename", "ok", "error", "waiting", "total"]}';
            $data['template_id'] = 'com_automsg.report';
            $data['subject'] = 'COM_AUTOMSG_REPORT_SUBJECT';
            $data['body'] = 'COM_AUTOMSG_REPORT_MSG';
            $table->save($data);
            Factory::getApplication()->enqueueMessage(Text::_('PLG_AUTOMSG_CREATE_REPORT_TEMPLATE_OK'), 'notice');
        }
    }
    private function tagstouppercase($text)
    {
        $pattern = "/\\{(.*?)\\}/i";
        if (preg_match_all($pattern, $text, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $replacement = strtoupper($match);
                $text = str_replace($match, $replacement, $text);
            }
        }
        return $text;
    }
    // check email config : should not be plaintext
    private function check_email_config()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__extensions');
        $query->where('type = ' . $db->quote('component'));
        $query->where('element = ' . $db->quote('com_mails'));
        $db->setQuery($query);
        $cfg = $db->loadObject();
        if (!$cfg) {
            Factory::getApplication()->enqueueMessage(Text::_('PLG_AUTOMSG_EMAIL_CONFIG_NOTOK'), 'error');
            return;
        }
        if (!$cfg->params) { // not yet created : create default
            $cfg->params = '{"mail_style":"plaintext","alternative_mailconfig":"0","copy_mails":"0","attachment_folder":""}';
        }
        if (strpos($cfg->params, 'plaintext') === false) {
            return;
        }
        $params = str_replace('plaintext', 'both', $cfg->params);
        $query = $db->getQuery(true)
                ->update('#__extensions')
                ->set($db->qn('params').' = '.$db->q($params))
                ->where('type = ' . $db->quote('component'))
                ->where('element = ' . $db->quote('com_mails'));
        $db->setQuery($query);
        $db->execute();
        Factory::getApplication()->enqueueMessage(Text::_('PLG_AUTOMSG_EMAIL_CONFIG_OK'), 'notice');

    }
    private function check_automsg_task()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from('#__scheduler_tasks');
        $query->where('type = ' . $db->quote('automsg'));
        $query->where('state = 1');
        $query->setLimit(1);
        $db->setQuery($query);
        $found = $db->loadResult();
        if ($found) {// Found in db => exit
            return;
        }
        $task = new TaskModel(array('ignore_request' => true));
        $table = $task->getTable();
        $data = [];
        $data['id']     = 0;
        $data['title']  = 'AutoMsg';
        $data['type']   = 'automsg';
        $data['state']  = 1; // activate
        $data['execution_rules'] = ["rule-type" => "interval-days",
                                    "interval-days" => "1",
                                    "exec-day" => "1",
                                    "exec-time" => "00:01"];
        $data['cron_rules']      = ["type" => "interval",
                                    "exp" => "P1D"];
        $notif = ["success_mail" => "0","failure_mail" => "1","fatal_failure_mail" => "1","orphan_mail" => "1"];
        $data['params'] = ["individual_log" => false,"log_file" => "","notifications" => $notif];
        $data['note']   = Text::_('PLG_AUTOMSG_CREATE_TASK_NOTE');
        $lastExec = Factory::getDate('now');
        $data['last_execution'] = $lastExec->toSql();
        $interval = new \DateInterval('P1D');
        $nextExec = $lastExec->add($interval);
        $data['next_execution'] = $nextExec->toSql();

        $table->save($data);
        Factory::getApplication()->enqueueMessage(Text::_('PLG_AUTOMSG_CREATE_TASK_OK'), 'notice');
    }
    private function createExtensionRoot()
    {
        $destination = JPATH_PLUGINS . '/system/' . $this->installerName;

        Folder::create($destination);

        File::copy(
            $this->dir . '/' . $this->installerName . '.xml',
            $destination . '/' . $this->installerName . '.xml'
        );
    }

    // Check if Joomla version passes minimum requirement
    private function passMinimumJoomlaVersion()
    {
        $j = new Version();
        $version = $j->getShortVersion();
        if (version_compare($version, $this->min_joomla_version, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf(
                    'NOT_COMPATIBLE_UPDATE',
                    '<strong>' . JVERSION . '</strong>',
                    '<strong>' . $this->min_joomla_version . '</strong>'
                ),
                'error'
            );

            return false;
        }

        return true;
    }

    // Check if PHP version passes minimum requirement
    private function passMinimumPHPVersion()
    {

        if (version_compare(PHP_VERSION, $this->min_php_version, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf(
                    'NOT_COMPATIBLE_PHP',
                    '<strong>' . PHP_VERSION . '</strong>',
                    '<strong>' . $this->min_php_version . '</strong>'
                ),
                'error'
            );

            return false;
        }

        return true;
    }
    private function installPackages()
    {
        $packages = Folder::folders($this->dir . '/packages');

        $packages = array_diff($packages, ['library_automsg']);

        foreach ($packages as $package) {
            if (! $this->installPackage($package)) {
                return false;
            }
        }
        // enable plugins
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $conditions = array(
            $db->qn('type') . ' = ' . $db->q('plugin'),
            $db->qn('element') . ' = ' . $db->quote('automsg')
        );
        $fields = array($db->qn('enabled') . ' = 1');

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (RuntimeException $e) {
            Log::add('unable to enable Plugins AutoMsg', Log::ERROR, 'jerror');
        }

        return true;
    }
    private function installPackage($package)
    {
        $tmpInstaller = new Installer();
        $installed = $tmpInstaller->install($this->dir . '/packages/' . $package);
        return $installed;
    }
    private function installLibrary()
    {
        if (! $this->installPackage('library_automsg')) {
            Factory::getApplication()->enqueueMessage(Text::_('ERROR_INSTALLATION_LIBRARY_FAILED'), 'error');
            return false;
        }
        Factory::getCache()->clean('_system');
        return true;
    }
    private function uninstallInstaller()
    {
        if (! is_dir(JPATH_PLUGINS . '/system/' . $this->installerName)) {
            return;
        }
        $this->delete([
            JPATH_PLUGINS . '/system/' . $this->installerName . '/language',
            JPATH_PLUGINS . '/system/' . $this->installerName,
        ]);
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->delete('#__extensions')
            ->where($db->quoteName('element') . ' = ' . $db->quote($this->installerName))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
        $db->setQuery($query);
        $db->execute();
        Factory::getCache()->clean('_system');
    }

    public function delete($files = [])
    {
        foreach ($files as $file) {
            if (is_dir($file)) {
                Folder::delete($file);
            }

            if (is_file($file)) {
                File::delete($file);
            }
        }
    }
}
