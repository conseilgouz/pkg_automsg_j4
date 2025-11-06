<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x/6.x
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace ConseilGouz\Component\Automsg\Administrator\Model;

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

class MessagesModel extends ListModel
{
    public function __construct($config = array(), ?MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'ids', 'ids',
                'articles', 'articles',
                'userid', 'userid',
                'state', 'state',
                'created','created',
                'modified','modified',
                'cr','cr'
                );
        }

        parent::__construct($config, $factory);
    }
    protected function getListQuery()
    {
        // Initialise variables.
        $db		= $this->getDatabase();
        $query	= $db->createQuery();

        // Select the required fields from the table.
        $query->select('sent, state,cr, GROUP_CONCAT(DISTINCT id) as ids, GROUP_CONCAT(DISTINCT article_id) as articles');
        $query->from('#__automsg');
        $query->group('sent,state,cr');
        // Filter by published state
        $published = $this->getState('filter.state');
        if (is_numeric($published)) {
            $query->where('state = '.(int) $published);
        } elseif ($published === '') {
            $query->where('(state IN (0, 1, 9))');
        }
        // Add the list ordering clause.
        $orderCol	= $this->state->get('list.ordering');
        $orderDirn	= $this->state->get('list.direction');
        $query->order($db->escape($orderCol.' '.$orderDirn));
        return $query;
    }

    protected function populateState($ordering = null, $direction = null)
    {
        // Load the filter state.
        $state = $this->getUserStateFromRequest($this->context.'.filter.state', 'filter_state', '', 'string');
        $this->setState('filter.state', $state);
        // List state information.
        parent::populateState('sent', 'DESC');
    }
    /**
     * Get logs data as a database iterator
     *
     * @param   integer[]|null  $pks  An optional array of log record IDs to load
     *
     * @return  JDatabaseIterator
     *
     * @since   3.9.0
     */
    public function getDataAsIterator($pks = null)
    {
        $db		= $this->getDatabase();
        $query = $this->getDataQuery($pks);

        $db->setQuery($query);

        return $db->getIterator();
    }

    public function getTable($type = 'Message', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($type, $prefix, $config);
    }


    /**
     * Get the query for loading data
     *
     * @param   integer[]|null  $pks  An optional array of log record IDs to load
     *
     * @return  JDatabaseQuery
     *
     * @since   3.9.0
     */
    private function getDataQuery($pks = null)
    {
        $db		= $this->getDatabase();
        $query = $db->createQuery();
        $query->select('a.*');
        $query->from('#__automsg as a');
        if (is_array($pks) && count($pks) > 0) {
            $query->where($db->quoteName('a.ids') . ' IN (' . implode(',', ArrayHelper::toInteger($pks)) . ')');
        }

        return $query;
    }

}
