<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x/6.x
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

class PublicModel extends ListModel
{
    public function __construct($public = array(), ?MVCFactoryInterface $factory = null)
    {
        if (empty($public['filter_fields'])) {
            $public['filter_fields'] = array(
                'id','email','ip','created', 'modified','state','country'
                );
        }

        parent::__construct($public, $factory);
    }
    protected function getListQuery()
    {
        // Initialise variables.
        $db		= $this->getDatabase();
        $query	= $db->createQuery();

        // Select the required fields from the table.

        $query->select('id,email,ip,country,state,created, modified');
        $query->from('#__automsg_public');
        $value = $this->getState('filter.state');
        if (is_numeric($value)) {
            $query->where('state = :value');
            $query->bind(':value', $value, \Joomla\Database\ParameterType::INTEGER);
        }
        
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
    public function delete(&$pks)
    {
        // Initialise variables.
        $pks = (array) $pks;
        $table = $this->getTable();

        // Iterate the items to delete each one.
        foreach ($pks as $i => $pk) {
            if ($table->load($pk)) {
                if (!$table->delete($pk)) {
                    $this->setError($table->getError());
                    return false;
                }
            } else {
                $this->setError($table->getError());
                return false;
            }
        }
        return true;
    }
    public function publish(&$pks)
    {
        // Initialise variables.
        $pks = (array) $pks;
        $table = $this->getTable();
        // Iterate the items to delete each one.
        foreach ($pks as $i => $pk) {
            if ($table->load($pk)) {
                if (!$table->updateState($pk, 1)) {
                    $this->setError($table->getError());
                    return false;
                }
            } else {
                $this->setError($table->getError());
                return false;
            }
        }
        return true;
    }
    public function unpublish(&$pks)
    {
        // Initialise variables.
        $pks = (array) $pks;
        $table = $this->getTable();
        // Iterate the items to delete each one.
        foreach ($pks as $i => $pk) {
            if ($table->load($pk)) {
                if (!$table->updateState($pk, 0)) {
                    $this->setError($table->getError());
                    return false;
                }
            } else {
                $this->setError($table->getError());
                return false;
            }
        }
        return true;
    }

}
