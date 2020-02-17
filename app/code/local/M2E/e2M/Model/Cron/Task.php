<?php

/**
 * Interface M2E_e2M_Model_Cron_Task
 */
interface M2E_e2M_Model_Cron_Task {

    /**
     * @param int $taskId
     * @param array $data
     *
     * @return array
     */
    public function process($taskId, $data);
}
