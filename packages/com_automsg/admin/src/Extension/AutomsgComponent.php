<?php
/**
 * Automsg Component  - Joomla 4.x/5.x Component 
  * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/
namespace ConseilGouz\Component\Automsg\Administrator\Extension;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;

class AutomsgComponent extends MVCComponent implements RouterServiceInterface
{
	use RouterServiceTrait;
}
