<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace ConseilGouz\Component\Automsg\Administrator\View\Users;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use ConseilGouz\Automsg\Helper\Automsg as AutomsgHelper;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    protected $taskstatus;
    /**
     * Display the view
     */
    public function display($tpl = null)
    {
        // Initialise variables.
        $this->items		= $this->get('Items');
        $this->pagination	= $this->get('Pagination');
        $this->state		= $this->get('State');
        // Check for errors.
        $errors = $this->get('Errors');
        if ($errors && \count($errors)) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();
        // $this->sidebar = JHtmlSidebar::render();

        parent::display($tpl);
    }

    protected function addToolbar()
    {
        $canDo = ContentHelper::getActions('com_automsg');
        $user = Factory::getApplication()->getIdentity();

        ToolbarHelper::title(Text::_('COM_AUTOMSG_USERS'), 'automsg.png');

        if ($canDo->get('core.admin')) {
            ToolbarHelper::divider();
            ToolbarHelper::inlinehelp();
            ToolbarHelper::preferences('com_automsg');
        }
    }
}
