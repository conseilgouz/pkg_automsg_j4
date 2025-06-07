<?php
/**
 * @package AutoMsg
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 */

namespace ConseilGouz\Module\AutomsgRegister\Site\Helper;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\IpHelper;

class AutomsgregisterHelper
{
    // ==============================================    AJAX Request 	============================================================
    public static function getAjax()
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        $app = Factory::getApplication();
        $input = $app->input->request;
        $timestp = $input->getInt('timestp');
        $email = $input->getString('email');
        $ip = IpHelper::getIp();
        $user = Factory::getApplication()->getIdentity();
        $id = $input->get('id');

        $lang = $app->getLanguage();
        $lang->load('mod_automsg_register');
        $module = self::getModuleById($id);
        $params = new Registry($module->params);
        $now = strtotime("now");
        $diff = date('s', $now - $timestp);
        if ($diff < 5) {
            return new JsonResponse(['error' => Text::_('AUTOMSG_REGISTER_ERR_CAPTCHA'),'timestp' => $now]);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = Text::_("AUTOMSG_REGISTER_ERR_EMAIL");
            return new JsonResponse(['error' => $emailErr,'timestp' => $now]);
        }
        if (self::checkEmail($email)) {
            $emailErr = Text::_("AUTOMSG_REGISTER_ERR_ALREADY");
            return new JsonResponse(['error' => $emailErr,'timestp' => $now]);
        }
        if (self::getEmailByIP($ip)) {
            self::updateEmail($email, $ip);
            return new JsonResponse(['success' => Text::_('AUTOMSG_REGISTER_UPDATED'),'timestp' => $now]);
        } else {
            $country = self::getCountry($ip);
            self::insertEmail($email, $ip,$country);
            return new JsonResponse(['success' => Text::_('AUTOMSG_REGISTER_OK'),'timestp' => $now]);
        }
    }
    // Get Module per ID
    private static function getModuleById($id)
    {
        $db =  Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('m.id, m.title, m.module, m.position, m.content, m.showtitle, m.params')
            ->from('#__modules AS m')
            ->where('m.id = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        return $db->loadObject();
    }
    // get email in db using its ip
    public static function getEmailByIP($ip)
    {
        $db =  Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('email')
            ->from('#__automsg_public')
            ->where('ip = :ip')
            ->bind(':ip', $ip, \Joomla\Database\ParameterType::STRING);
        $db->setQuery($query);
        return $db->loadResult();
    }
    // get email in db using its ip
    public static function checkEmail($email)
    {
        $db =  Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('ip')
            ->from('#__automsg_public')
            ->where('email = :email')
            ->bind(':email', $email, \Joomla\Database\ParameterType::STRING);
        $db->setQuery($query);
        $result =  $db->loadResult();
        if ($result) { // found in automsg_public
            return $result;
        }
        // check in users 
        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__users')
            ->where('email = :email')
            ->bind(':email', $email, \Joomla\Database\ParameterType::STRING);
        $db->setQuery($query);
        $result =  $db->loadResult();
        return $result;
    }
    // insert email in #__automsg_public
    public static function updateEmail($email, $ip)
    {
        $db =  Factory::getContainer()->get(DatabaseInterface::class);
        $sDate = gmdate("Y-m-d H:i:s", time());
        $query = $db->getQuery(true);
        $fields = array(
            $db->quoteName('email') . ' = :email',
            $db->quoteName('modified') . ' = :modified'
        );
        $conditions = array(
            $db->quoteName('ip') . ' = :ip', 
        );
        $query->update($db->quoteName('#__automsg_public'))->set($fields)->where($conditions);
        $query->bind(':email', $email, \Joomla\Database\ParameterType::STRING)
               ->bind(':ip', $ip, \Joomla\Database\ParameterType::STRING)
               ->bind(':modified', $sDate, \Joomla\Database\ParameterType::STRING);
        $db->setQuery($query);
        $db->execute();
    }
    // insert email in #__automsg_public
    public static function insertEmail($email, $ip,$country)
    {
        $db =  Factory::getContainer()->get(DatabaseInterface::class);
        $sDate = gmdate("Y-m-d H:i:s", time());
        $query = $db->getQuery(true);
        $columns = array('ip','email','created','state');
        $values = array($db->quote($ip),$db->quote($email),$db->quote($sDate),1);
        $query->insert($db->quoteName('#__automsg_public'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            return false;
        }
    }
    private static function getCountry($ip) {
        $iplocate = 'https://www.iplocate.io/api/lookup/';
        $apikey = "e468c23c8daf64701f9d96e16b677e6f";
        $app = Factory::getApplication();
        if (($ip == '::1') || ($ip == '127.0.0.1')) { // local host
            return true;
        }
        apikey = $params->get('iplocatekey','e468c23c8daf64701f9d96e16b677e6f');
        $response = self::getIPLocate_via_curl($iplocate.$ip.'?apikey='.$apikey);
        $country = "";
        if ($response) { // IPLocate OK
            $json_array = json_decode($response);
            if ($json_array->country_code == "") { // IPLocate perdu : on suppose hackeur
                echo sprintf(Text::_('AUTOMSG_REGISTER_COUNTRY_NOTFOUND'), $ip);
                return $country;
            }
            $country = $json_array->country_code;
        }
        return $country
    }
    // get country using IPLocate
    public static function getIPLocate_via_curl($url)
    {
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $url);
            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
        } catch (\RuntimeException $e) {
            return null;
        }
    }
    
}
