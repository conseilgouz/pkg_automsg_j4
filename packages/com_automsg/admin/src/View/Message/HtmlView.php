<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * Version			: 4.0.0
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/

namespace ConseilGouz\Component\AutoMsg\Administrator\View\Message;

// No direct access
\defined('_JEXEC') or die;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $pagination;
    protected $state;
    protected $page;

    /**
     * Display the view
     */
    public function display($tpl = null)
    {

        $this->form		= $this->get('Form');
        $this->message	= $this->get('Item');
        $this->formControl = $this->form ? $this->form->getFormControl() : null;

        $this->addToolbar();

        // $this->sidebar = HtmlSidebar::render();
        parent::display($tpl);
    }

    /**
     * Add the message title and toolbar.
     *
     * @since	1.6
     */
    protected function addToolbar()
    {
        $canDo = ContentHelper::getActions('com_automsg');
        $state = $this->get('State');
        /*$input = Factory::getApplication()->input;
        $input->setVar('hidemainmenu', true);
        */
        $user		= Factory::getUser();
        $userId		= $user->get('id');
        if (!isset($this->message->sent)) {
            $this->message->sent = null;
        }
        ToolBarHelper::title( Text::_('CG_PX_MESSAGE_EDIT'), '#xs#.png');

        ToolbarHelper::custom('message.retry','envelope-opened','', Text::_('COM_AUTOMSG_MESSAGES_BUTTON_RETRY'),false);

        if (empty($this->message->id)) {
            ToolBarHelper::cancel('message.cancel');
        } else {
            ToolBarHelper::cancel('message.cancel', 'JTOOLBAR_CLOSE');
        }
        ToolbarHelper::inlinehelp();

    }

}
