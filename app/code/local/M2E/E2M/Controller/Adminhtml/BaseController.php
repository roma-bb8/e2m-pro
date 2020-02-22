<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class Ess_E2M_Controller_Adminhtml_BaseController
 */
class M2E_E2M_Controller_Adminhtml_BaseController extends Mage_Adminhtml_Controller_Action {

    const HTTP_INTERNAL_ERROR = 500;

    //########################################

    /**
     * @param Exception $e
     *
     * @return Zend_Controller_Response_Abstract
     * @throws Zend_Controller_Response_Exception
     */
    public function renderAjaxException(Exception $e) {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');
        $dataHelper->logException($e);

        $response = $this->getResponse();
        $response->setHttpResponseCode(self::HTTP_INTERNAL_ERROR);
        $response->setBody(Mage::helper('core')->jsonEncode(array(
            'error' => true,
            'message' => $e->getMessage()
        )));

        return $response;
    }

    //########################################

    /**
     * @param $action
     *
     * @throws Zend_Controller_Response_Exception
     */
    final public function dispatch($action) {

        try {
            parent::dispatch($action);
        } catch (Exception $e) {
            if ($this->getRequest()->isAjax()) {
                $this->renderAjaxException($e);
            }

            $this->_getSession()->addError($e->getMessage());

            $this->_redirect('*/e2m/index');
        }
    }
}
