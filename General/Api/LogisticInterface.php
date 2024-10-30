<?php 
namespace Ecpay\General\Api;
 
use Magento\Framework\Controller\Result\Json;
 
interface LogisticInterface {

    /**
     * changeStore
     * @param string $orderId
     * @param string $protectCode
     * @return Json
     */
    public function changeStore($orderId, $protectCode);

    /**
     * createOrder
     * @param string $orderId
     * @param string $protectCode
     * @return Json
     */
    public function createOrder($orderId, $protectCode);

    /**
     * printOrder
     * @param string $orderId
     * @param string $protectCode
     * @return Json
     */
    public function printOrder($orderId, $protectCode);

    /**
     * getLogisticMainConfig
     * @return Json
     */
    public function getLogisticMainConfig();

    /**
     * getShippingMethod
     * @param string $orderId
     * @param string $protectCode
     * @return Json
     */
    public function getShippingMethod($orderId, $protectCode);

    /**
     * getShippingTag
     * @param string $orderId
     * @param string $protectCode
     * @return Json
     */
    public function getShippingTag($orderId, $protectCode);

    
    
}