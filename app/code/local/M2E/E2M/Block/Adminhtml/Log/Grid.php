<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Block_Adminhtml_Log_Grid
 */
class M2E_E2M_Block_Adminhtml_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    /**
     * @param string $value
     * @param M2E_E2M_Model_Log $row
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param bool $isExport
     *
     * @return string
     */
    public function callbackColumnType($value, $row, $column, $isExport) {

        $statusColors = array(
            M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS => 'green',
            M2E_E2M_Helper_Data::TYPE_REPORT_WARNING => 'orange',
            M2E_E2M_Helper_Data::TYPE_REPORT_ERROR => 'red'
        );

        $type = $row->getData('type');
        $color = isset($statusColors[$type]) ? $statusColors[$type] : 'black';

        return sprintf('<span style="color:%s">%s</span>', $color, $value);
    }

    //----------------------------------------

    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function _prepareColumns() {

        $dataHelper = Mage::helper('e2m');

        //----------------------------------------

        $this->addColumn('id', array(
            'header' => $dataHelper->__('ID'),
            'align' => 'left',
            'width' => '*',
            'type' => 'text',
            'filter_index' => 'id',
            'index' => 'id'
        ));

        $this->addColumn('task_id', array(
            'header' => $dataHelper->__('Task ID'),
            'align' => 'left',
            'width' => '*',
            'type' => 'text',
            'sortable' => false,
            'filter_index' => 'task_id',
            'index' => 'task_id'
        ));

        $this->addColumn('description', array(
            'header' => $dataHelper->__('Description'),
            'align' => 'left',
            'width' => '*',
            'type' => 'text',
            'index' => 'description'
        ));

        $this->addColumn('type', array(
            'header' => $dataHelper->__('Type'),
            'align' => 'left',
            'width' => '*',
            'sortable' => false,
            'type' => 'options',
            'index' => 'type',
            'options' => array(
                M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS => $dataHelper->__('Success'),
                M2E_E2M_Helper_Data::TYPE_REPORT_WARNING => $dataHelper->__('Warning'),
                M2E_E2M_Helper_Data::TYPE_REPORT_ERROR => $dataHelper->__('Error')
            ),
            'frame_callback' => array($this, 'callbackColumnType')
        ));

        return parent::_prepareColumns();
    }

    //########################################

    /**
     * @inheritDoc
     */
    protected function _prepareCollection() {

        /** @var M2E_E2M_Model_Resource_Log_Collection $logCollection */
        $logCollection = Mage::getModel('e2m/Log')->getCollection();

        $this->setCollection($logCollection);

        return parent::_prepareCollection();
    }

    //########################################

    /**
     * @inheritDoc
     */
    public function __construct() {
        parent::__construct();

        $this->setId('logGrid');

        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setFilterVisibility(false);
        $this->setUseAjax(true);
    }
}
