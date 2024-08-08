<?php 
namespace Ecpay\General\Api;
 
 
interface InvoiceInterface {

    /**
     *
     * @param string $barcode
     * @return []
     */
    public function checkBarcode($barcode);

    /**
     *
     * @param string $loveCode
     * @return []
     */
    public function checkLoveCode($loveCode);

    /**
     *
     * @param string $carrierNumber
     * @return []
     */
    public function checkCitizenDigitalCertificate($carrierNumber);

    /**
     *
     * @param string $businessNumber
     * @return []
     */
    public function checkBusinessNumber($businessNumber);

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