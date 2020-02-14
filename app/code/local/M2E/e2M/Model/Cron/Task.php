<?php

interface M2E_e2M_Model_Cron_Task {

    /**
     * @param mixed $data
     *
     * @return array
     */
    public function process($data);
}
