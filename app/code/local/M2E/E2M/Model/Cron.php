<?php

class M2E_E2M_Model_Cron {

    /**
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function process() {

        $coreHelper = Mage::helper('core');

        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksTableName = $resource->getTableName('m2e_e2m_cron_tasks');
        $tasks = $connRead->select()->from($cronTasksTableName)->order('id DESC')->query();

        //----------------------------------------

        $handlers = array();
        while ($task = $tasks->fetch(PDO::FETCH_ASSOC)) {
            if ($task['is_running'] || $task['pause']) {
                continue;
            }

            $connWrite->update($cronTasksTableName, array(
                'is_running' => true
            ), array('id = ?' => $task['id']));

            try {

                /** @var M2E_E2M_Model_Cron_Task $taskModel */
                $taskModel = Mage::getModel('e2m/' . str_replace('M2E_E2M_Model_', '', $task['instance']));

                $data = $taskModel->process($task['id'], $coreHelper->jsonDecode($task['data']));
                $instance = lcfirst(substr($task['instance'], strrpos($task['instance'], '_') + 1));
                $handlers[] = array(
                    'handler' => $instance . 'Handler',
                    'data' => $data
                );

            } catch (Exception $e) {
                $dataHelper->logException($e);
            } finally {
                $connWrite->update($cronTasksTableName, array(
                    'is_running' => false
                ), array('id = ?' => $task['id']));
            }
        }

        return $handlers;
    }
}
