<?php
/**
 * @component     AutoMsg - Joomla 4.x/5.x
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
**/

namespace ConseilGouz\Component\Automsg\Administrator\Table;

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Utilities\ArrayHelper;

class PublicTable extends Table implements VersionableTableInterface
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
        $this->typeAlias = 'com_automsg.public';

        parent::__construct('#__automsg_public', 'id', $db);

        $this->created = Factory::getDate()->toSql();
        $this->modified = Factory::getDate()->toSql();
        
        $this->db = $db;
    }
    /**
     *  setState method
     *
     *  @param   integer  state
     */
    public function setState($state)
    {
        $this->state = $state;
    }
    /**
     *  Store method
     *
     *  @param   string  $key  The config name
     */
    public function updateState($key = 'id', $state = 0)
    {
        $db    = $this->db;
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
        $data->state = $state;
        $sDate = gmdate("Y-m-d H:i:s", time());
        $data->modified = $sDate;
        if ($exists) {
            return $db->updateObject($table, $data, 'id');
        }

        return $db->insertObject($table, $data);
    }
    public function deletePublic($email = 'email')
    {
        $db    = $this->db;
        $table = $this->_tbl;
        // Check if key exists
        $result = $db->setQuery(
            $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName($this->_tbl))
                ->where($db->quoteName('email') . ' = ' . $db->quote($email))
        )->loadResult();

        $exists = $result ? true : false;

        if (!$exists) {
            return false;
        }
        $query = $db->getQuery(true);
        $query->delete($db->quoteName($this->_tbl))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $result, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $result = $db->execute();
        $data = new \stdClass();
        return $result;
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
