<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Interface M2E_E2M_Model_Cron_Task
 */
interface M2E_E2M_Model_Cron_Task {

    /**
     * @param int $taskId
     * @param array $data
     *
     * @return array
     */
    public function process($taskId, $data);
}
