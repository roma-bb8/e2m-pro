<?php

/**
 * Class M2E_E2M_Block_Adminhtml__Log_Grid
 */
class M2E_E2M_Block_Adminhtml_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    //########################################

    public function __construct() {
        parent::__construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('logGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setFilterVisibility(false);
        //$this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection() {
        $this->setCollection(Mage::getModel('e2m/Log')->getCollection());

        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('id', array(
            'header' => Mage::helper('e2m')->__('ID'),
            'align' => 'left',
            'width' => '*',
            'type' => 'text',
            'filter_index' => 'id',
            'index' => 'id'
        ));

        $this->addColumn('task_id', array(
            'header' => Mage::helper('e2m')->__('Task ID'),
            'align' => 'left',
            'width' => '*',
            'type' => 'text',
            'sortable' => false,
            'filter_index' => 'task_id',
            'index' => 'task_id'
        ));

        $this->addColumn('description', array(
            'header' => Mage::helper('e2m')->__('Description'),
            'align' => 'left',
            'width' => '*',
            'type' => 'text',
            'index' => 'description'
        ));

        $this->addColumn('type', array(
            'header' => Mage::helper('e2m')->__('Type'),
            'align' => 'left',
            'width' => '*',
            'sortable' => false,
            'type' => 'options',
            'index' => 'type',
            'options' => array(
                M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS => Mage::helper('e2m')->__('Success'),
                M2E_E2M_Helper_Data::TYPE_REPORT_WARNING => Mage::helper('e2m')->__('Warning'),
                M2E_E2M_Helper_Data::TYPE_REPORT_ERROR => Mage::helper('e2m')->__('Error')
            ),
            'frame_callback' => array($this, 'callbackColumnStatus')
        ));

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnStatus($value, $row, $column, $isExport) {
        $type = $row->getData('type');
        $statusColors = array(
            M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS => 'green',
            M2E_E2M_Helper_Data::TYPE_REPORT_WARNING => 'orange',
            M2E_E2M_Helper_Data::TYPE_REPORT_ERROR => 'red'
        );

        $color = isset($statusColors[$type]) ? $statusColors[$type] : 'black';
        return '<span style="color: ' . $color . ';">' . $value . '</span>';
    }

    //########################################
}
