<?php
/**
 * @package    AutoMsg
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (C) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 * @license    GNU/GPLv3
 */
// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Component\Scheduler\Administrator\Model\TaskModel;
use Joomla\Database\DatabaseInterface;

class plgtaskAutomsgInstallerScript
{
    public function uninstall($parent)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from('#__scheduler_tasks');
        $query->where('type = ' . $db->quote('automsg'));
        $db->setQuery($query);
        $found = $db->loadObjectList();
        foreach ($found as $one) { // should be only one, but in case, just loop
            $pks['id'] = $one->id;
            $task = new TaskModel(array('ignore_request' => true));
            $table = $task->getTable();
            $table->delete($pks);
        }
        return true;
    }
}
