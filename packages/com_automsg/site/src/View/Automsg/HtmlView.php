<?php
/**
 * Automsg Component  - Joomla 4.x/5.x/6.x Component 
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/
namespace ConseilGouz\Component\Automsg\Site\View\Automsg;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\User;
use Joomla\Component\Users\Administrator\Helper\Mfa;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Profile view class for Users.
 *
 * @since  1.6
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Profile form data for the user
     *
     * @var  User
     */
    protected $data;

    /**
     * The Form object
     *
     * @var  \Joomla\CMS\Form\Form
     */
    protected $form;

    /**
     * The page parameters
     *
     * @var  \Joomla\Registry\Registry|null
     */
    protected $params;

    /**
     * The model state
     *
     * @var  CMSObject
     */
    protected $state;

    /**
     * An instance of DatabaseDriver.
     *
     * @var    DatabaseDriver
     * @since  3.6.3
     *
     * @deprecated  5.0 Will be removed without replacement
     */
    protected $db;

    /**
     * The page class suffix
     *
     * @var    string
     * @since  4.0.0
     */
    protected $pageclass_sfx = '';

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void|boolean
     *
     * @since   1.6
     * @throws  \Exception
     */
    public function display($tpl = null)
    {
        $model = $this->getModel();
        $model->setUseExceptions(true);

        try {
            $this->data               = $model->getData();
            $this->form               = $model->getForm();
            $this->state              = $model->getState();
            $this->params             = $this->state->get('params');
            $this->db                 = Factory::getContainer()->get(DatabaseInterface::class);
        } catch (\Exception $e) {
	        $app   =Factory::getApplication();
	        $app->enqueueMessage( $e->getMessage(), 'error');
            $app->setHeader('status', 403, true);
            throw new GenericDataException($e->getMessage(), 403,$e);
        }

        if ( $this->get('Errors')) {
        }
        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document
     *
     * @return  void
     *
     * @since   1.6
     * @throws  \Exception
     */
    protected function prepareDocument()
    {
    }
}
