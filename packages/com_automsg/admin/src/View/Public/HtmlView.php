<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x/6.x
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace ConseilGouz\Component\Automsg\Administrator\View\Public;

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
        $model = $this->getModel();
        $model->setUseExceptions(true);

        try {
            $this->items		= $model->getItems();
            $this->pagination	= $model->getPagination();
            $this->state		= $model->getState();
        } catch (\Exception $e) {
            throw new GenericDataException($e->getMessage(), 500, $e);
        }
        $this->addToolbar();
        // $this->sidebar = JHtmlSidebar::render();

        parent::display($tpl);
    }

    protected function addToolbar()
    {
        $canDo = ContentHelper::getActions('com_automsg');
        $user = Factory::getApplication()->getIdentity();

        ToolbarHelper::title(Text::_('COM_AUTOMSG_PUBLIC'), 'automsg.png');
        if ($canDo->get('core.edit.state')) {
            ToolBarHelper::divider();
            ToolBarHelper::publish('public.publish', 'JTOOLBAR_PUBLISH', true);
            ToolBarHelper::unpublish('public.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            ToolBarHelper::divider();
        }

        if ($canDo->get('core.edit.state')) {
            ToolBarHelper::deleteList('', 'public.delete');
        }

        if ($canDo->get('core.admin')) {
            ToolbarHelper::divider();
            ToolbarHelper::preferences('com_automsg');
        }
    }
}
