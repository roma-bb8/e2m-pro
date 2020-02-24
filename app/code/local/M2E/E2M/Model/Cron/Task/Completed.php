<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Model_Cron_Task_Completed
 */
class M2E_E2M_Model_Cron_Task_Completed implements M2E_E2M_Model_Cron_Task {

    const INSTANCE = 'Cron_Task_Completed';

    const COMPLETED = 100;

    const MAX_CREATED = '+30 minutes';
    const MAX_UPDATE = '+10 minutes';

    //########################################

    /**
     * @inheritDoc
     */
    public function completed($taskId, $data) {

    }

    //########################################

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function process($taskId, $data) {

        $coreHelper = Mage::helper('core');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        $tasks = $connRead->select()->from($cronTasksInProcessingTableName)
            ->where('instance <> ?', self::INSTANCE)->query();
        while ($task = $tasks->fetch(PDO::FETCH_ASSOC)) {
            if (self::COMPLETED === (int)$task['progress']) {
                $connWrite->delete($cronTasksInProcessingTableName, array(
                    'id = ?' => $task['id']
                ));

                /** @var M2E_E2M_Model_Cron_Task $taskModel */
                $taskModel = Mage::getModel('e2m/' . $task['instance']);

                $taskModel->completed($task['id'], $coreHelper->jsonDecode($task['data']));

                continue;
            }

            $dateTimeZone = new DateTimeZone('UTC');
            $current = new DateTime('now', $dateTimeZone);

            if (null !== $task['updated']) {

                $updated = new DateTime($task['updated'], $dateTimeZone);
                $updated->modify(self::MAX_UPDATE);

                if ($current->getTimestamp() > $updated->getTimestamp()) {
                    $connWrite->delete($cronTasksInProcessingTableName, array(
                        'id = ?' => $task['id']
                    ));

                    continue;
                }
            }

            $created = new DateTime($task['created'], $dateTimeZone);
            $created->modify(self::MAX_CREATED);

            if ($current->getTimestamp() > $created->getTimestamp()) {
                $connWrite->delete($cronTasksInProcessingTableName, array(
                    'id = ?' => $task['id']
                ));

                continue;
            }
        }

        return array();
    }
}
