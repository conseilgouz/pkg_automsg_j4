<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * Version			: 4.0.0
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace ConseilGouz\Component\Automsg\Administrator\View\Histos;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
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

        ToolbarHelper::title(Text::_('COM_AUTOMSG_PAGES'), 'automsg.png');

        if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_automsg', 'core.create'))) > 0) {
            ToolbarHelper::addNew('histo.add');
        }

        if (($canDo->get('core.edit')) || ($canDo->get('core.edit.own'))) {
            ToolbarHelper::editList('histo.edit');
        }

        if ($canDo->get('core.edit.state')) {
            ToolbarHelper::divider();
            ToolbarHelper::publish('histo.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('histo.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }
        if (isset($this->state) && $this->state->get('filter.state') == -2 && $canDo->get('core.delete')) {
            ToolBarHelper::deleteList('', 'histos.delete', 'JTOOLBAR_EMPTY_TRASH');
        } elseif ($canDo->get('core.edit.state')) {
            ToolBarHelper::trash('histos.trash');
        }
        if ($canDo->get('core.admin')) {
            ToolbarHelper::divider();
            ToolbarHelper::inlinehelp();
            ToolbarHelper::preferences('com_automsg');
        }
    }
}
