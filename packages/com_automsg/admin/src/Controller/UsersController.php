<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace ConseilGouz\Component\Automsg\Administrator\Controller;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Scheduler\Administrator\Scheduler\Scheduler;
use Joomla\Database\DatabaseInterface;
use ConseilGouz\Component\Automsg\Administrator\Model\ConfigModel;

/**
 * messages controller class
 */
class UsersController extends FormController
{
    protected $text_prefix = 'AUTOMSG';
}
