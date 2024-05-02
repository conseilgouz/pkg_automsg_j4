<?php
/**
 * Automsg Component  - Joomla 4.x/5.x Component 
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2023 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/
namespace ConseilGouz\Component\Automsg\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Routing class from com_users
 *
 * @since  3.2
 */
class Router extends RouterView
{
    /**
     * Users Component router constructor
     *
     * @param   SiteApplication  $app   The application object
     * @param   AbstractMenu     $menu  The menu object to work with
     */
    public function __construct(SiteApplication $app, AbstractMenu $menu)
    {
        $automsg = new RouterViewConfiguration('automsg');
        $automsg->addLayout('edit');
        $this->registerView($automsg);

        parent::__construct($app, $menu);

    }

    /**
     * Get the method ID from a URL segment
     *
     * @param   string  $segment  The URL segment
     * @param   array   $query    The URL query parameters
     *
     * @return integer
     * @since 4.2.0
     */
    public function getMethodId($segment, $query)
    {
        return (int) $segment;
    }

    /**
     * Get a segment from a method ID
     *
     * @param   integer  $id     The method ID
     * @param   array    $query  The URL query parameters
     *
     * @return int[]
     * @since 4.2.0
     */
    public function getMethodSegment($id, $query)
    {
        return [$id => (int) $id];
    }
}
