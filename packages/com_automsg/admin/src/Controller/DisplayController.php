<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * Version			: 4.0.0
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/

namespace ConseilGouz\Component\Automsg\Administrator\Controller;

\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class DisplayController extends BaseController {
	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $default_view = 'config';

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
	 *
	 * @return  static|boolean   This object to support chaining or false on failure.
	 *
	 * @since   3.1
	 */
	public function display($cachable = false, $urlparams = false)
	{
        $app = Factory::getApplication();
        $this->input = $app->getInput();
		$view   = $this->input->get('view', 'config');
		$layout = $this->input->get('layout', 'default');
		$id     = $this->input->getInt('id');

		parent::display();

		return $this;
	}


}
