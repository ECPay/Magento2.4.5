<?php 
namespace Ecpay\General\Api;
 
 
interface InvoiceInterface {

    /**
     *
     * @param string $orderId
     * @param string $protectCode
     * @return []
     */
    public function createInvoice($orderId, $protectCode);

    /**
     *
     * @param string $orderId
     * @param string $protectCode
     * @return []
     */
    public function invalidInvoice($orderId, $protectCode);

    /**
     *
     * @param string $orderId
     * @param string $protectCode
     * @return []
     */
    public function getInvoiceTag($orderId, $protectCode);

    /**
     *
     * @return []
     */
    public function getInvoiceMainConfig();
}