<?php 
namespace Ecpay\General\Api;
 
 
interface LogisticInterface {

    /**
     * changeStore
     * @param string $orderId
     * @param string $protectCode
     * @return []
     */
    public function changeStore($orderId, $protectCode);

    /**
     * createOrder
     * @param string $orderId
     * @param string $protectCode
     * @return []
     */
    public function createOrder($orderId, $protectCode);

    /**
     * printOrder
     * @param string $orderId
     * @param string $protectCode
     * @return []
     */
    public function printOrder($orderId, $protectCode);

    /**
     * getLogisticMainConfig
     * @return []
     */
    public function getLogisticMainConfig();

    /**
     * getShippingMethod
     * @param string $orderId
     * @param string $protectCode
     * @return []
     */
    public function getShippingMethod($orderId, $protectCode);

    /**
     * getShippingTag
     * @param string $orderId
     * @param string $protectCode
     * @return []
     */
    public function getShippingTag($orderId, $protectCode);

    
    
}