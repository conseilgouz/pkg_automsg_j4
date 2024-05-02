<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * Version			: 4.0.0
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
**/

namespace ConseilGouz\Component\Automsg\Administrator\Table;

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Utilities\ArrayHelper;

class HistoTable extends Table implements VersionableTableInterface
{
    /**
     * An array of key names to be json encoded in the bind function
     *
     * @var    array
     * @since  4.0.0
     */
    protected $_jsonEncode = ['params', 'metadata', 'urls', 'images'];
    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     * @since  4.0.0
     */
    protected $_supportNullValue = true;


    protected $updated = null;

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    public function __construct(DatabaseDriver $db)
    {
        $this->typeAlias = 'com_automsg.histo';

        parent::__construct('#__automsg', 'id', $db);

        $this->created = Factory::getDate()->toSql();
        $this->updated = Factory::getDate()->toSql();
    }

    /**
     *  Store method
     *
     *  @param   string  $key  The config name
     */
    public function updateState($key = 'id')
    {
        $db    = Factory::getDBo();
        $table = $this->_tbl;
        $key   = empty($this->id) ? $key : $this->id;

        // Check if key exists
        $result = $db->setQuery(
            $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName($this->_tbl))
                ->where($db->quoteName('id') . ' = ' . $db->quote($key))
        )->loadResult();

        $exists = $result > 0 ? true : false;

        // Prepare object to be saved
        $data = new \stdClass();
        $data->id   = $key;
        $data->state = $this->state;
        $data->updated = $this->updated;
        if ($exists) {
            return $db->updateObject($table, $data, 'id');
        }

        return $db->insertObject($table, $data);
    }
    public function publish($pks = null, $state = 1, $userId = 0)
    {
        $k = $this->_tbl_key;
        ArrayHelper::toInteger($pks);
        $userId = (int) $userId;
        $state  = (int) $state;

        $db = $this->getDbo();
        if (empty($pks)) {
            if ($this->$k) {
                $pks = array($this->$k);
            } else {
                $this->setError(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
                return false;
            }
        }
        // ???? $table = Table::getInstance('WaitingTable', __NAMESPACE__ . '\\', array('dbo' => $db));
        $table = $this->_tbl;
        foreach ($pks as $pk) {
            if(!$table->load($pk)) {
                $this->setError($table->getError());
            }
            $table->state = $state;
            $table->updated = Factory::getDate()->toSql();
            $table->check();
            if (!$table->updateState()) {
                $this->setError($table->getError());
            }
        }
        return count($this->getErrors()) == 0;
    }
    /**
     * Get the type alias for the history table
     *
     * @return  string  The alias as described above
     *
     * @since   4.0.0
     */
    public function getTypeAlias()
    {
        return $this->typeAlias;
    }
    public function getKeyName($multiple = false)
    {
        return 'id';
    }
}
