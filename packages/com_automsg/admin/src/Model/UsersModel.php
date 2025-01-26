<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace ConseilGouz\Component\Automsg\Administrator\Model;

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

class UsersModel extends ListModel
{
    public function __construct($config = array(), MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id','name','username','email', 'email',
                'lastvisitDate', 'block','activation',
                'value'
                );
        }

        parent::__construct($config, $factory);
    }
    protected function getListQuery()
    {
        // Initialise variables.
        $db		= $this->getDatabase();
        $query	= $db->getQuery(true);

        // Select the required fields from the table.

        $query->select('u.id,u.name,u.username,u.email,u.lastvisitDate,u.block,u.activation, CASE when p.profile_value LIKE '.$db->q('%Oui%').' then 1 else 0  end as value');
        $query->from('#__users u');
        $query->join('LEFT', '#__user_profiles p ON u.id = p.user_id');
        $query->where('p.profile_key = :key');
        $key = "profile_automsg.automsg";
        $query->bind(':key', $key, \Joomla\Database\ParameterType::STRING);
        $value = $this->getState('filter.state');
        if (is_numeric($value)) {
            if ($value == 1) {
                $value = '%Oui%';
            } else {
                $value = '%Non%';
            }
            $query->where('p.profile_value like :value');
            $query->bind(':value', $value, \Joomla\Database\ParameterType::STRING);
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
        parent::populateState('id', 'DESC');
    }
}
