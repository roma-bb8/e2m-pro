<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Helper_Progress
 */
class M2E_E2M_Helper_Progress {

    const POSTFIX = '/progress';

    //########################################

    /** @var int[] $progress */
    private $progress = array();

    //########################################

    /**
     * @param string $tag
     * @param int $progress
     *
     * @return $this
     */
    public function setProgressByTag($tag, $progress) {

        $this->progress[$tag] = $progress;

        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $coreConfigDataTableName = $resource->getTableName('core_config_data');

        $connWrite->delete($coreConfigDataTableName, array(
            'path = ?' => M2E_E2M_Helper_Data::PREFIX . $tag . M2E_E2M_Helper_Progress::POSTFIX
        ));

        $connWrite->insert($coreConfigDataTableName, array(
            'path' => M2E_E2M_Helper_Data::PREFIX . $tag . M2E_E2M_Helper_Progress::POSTFIX,
            'value' => $this->progress[$tag]
        ));

        return $this;
    }

    /**
     * @param string $tag
     * @param bool $reload
     *
     * @return mixed
     */
    public function getProgressByTag($tag, $reload = false) {
        if (isset($this->progress[$tag]) && !$reload) {
            return $this->progress[$tag];
        }

        $resource = Mage::getSingleton('core/resource');
        $this->progress[$tag] = (int)$resource->getConnection('core_read')->select()
            ->from($resource->getTableName('core_config_data'), 'value')
            ->where('path = ?', M2E_E2M_Helper_Data::PREFIX . $tag . M2E_E2M_Helper_Progress::POSTFIX)
            ->query()->fetchColumn();

        return $this->progress[$tag];
    }

    //----------------------------------------

    /**
     * @param string $tag
     * @param bool $reload
     *
     * @return bool
     */
    public function isCompletedProgressByTag($tag, $reload = false) {
        return $this->getProgressByTag($tag, $reload) === 100;
    }
}
