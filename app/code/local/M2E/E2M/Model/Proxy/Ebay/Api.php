<?php

class M2E_E2M_Model_Proxy_Ebay_Api {

    /** @var Ess_M2ePro_Model_Ebay_Connector_Protocol $eBayConnectorProtocol */
    private $eBayConnectorProtocol;

    /** @var Ess_M2ePro_Model_Connector_Connection_Request $connectorConnectionRequest */
    private $connectorConnectionRequest;

    /** @var Ess_M2ePro_Model_Connector_Connection_Single $connectorConnectionSingle */
    private $connectorConnectionSingle;

    //########################################

    /**
     * @param array $request
     *
     * @return array
     * @throws Exception
     */
    public function sendRequest(array $request) {

        $this->connectorConnectionRequest->setComponentVersion($this->eBayConnectorProtocol->getComponentVersion());
        $this->connectorConnectionRequest->setComponent($this->eBayConnectorProtocol->getComponent());
        $this->connectorConnectionRequest->setCommand((array)$request['command']);
        $this->connectorConnectionRequest->setData($request['data']);

        $this->connectorConnectionSingle->setRequest($this->connectorConnectionRequest);
        $this->connectorConnectionSingle->process();

        return $this->connectorConnectionSingle->getResponse()->getData();
    }

    //########################################

    public function __construct() {

        $this->eBayConnectorProtocol = Mage::getModel('M2ePro/Ebay_Connector_Protocol');
        $this->connectorConnectionSingle = Mage::getModel('M2ePro/Connector_Connection_Single');
        $this->connectorConnectionRequest = Mage::getModel('M2ePro/Connector_Connection_Request');
    }
}
