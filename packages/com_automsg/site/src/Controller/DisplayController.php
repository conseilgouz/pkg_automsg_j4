<?php
/**
 * Automsg Component  - Joomla 4.x/5.x Component 
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2023 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/

namespace ConseilGouz\Component\Automsg\Site\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;

class DisplayController extends BaseController {

    public function display($cachable = false, $urlparams = false) {
        $view = Factory::getApplication()->getInput()->getCmd('view', 'automsg');
        Factory::getApplication()->getInput()->set('view', $view);

        parent::display($cachable, $urlparams);

        return $this;
    }
}
