<?php 
namespace Ecpay\General\Api;
 
use Magento\Framework\Controller\Result\Json;
 
interface InvoiceInterface {

    /**
     *
     * @param string $barcode
     * @return Json
     */
    public function checkBarcode($barcode);

    /**
     *
     * @param string $loveCode
     * @return Json
     */
    public function checkLoveCode($loveCode);

    /**
     *
     * @param string $carrierNumber
     * @return Json
     */
    public function checkCitizenDigitalCertificate($carrierNumber);

    /**
     *
     * @param string $businessNumber
     * @return Json
     */
    public function checkBusinessNumber($businessNumber);

    /**
     *
     * @param string $orderId
     * @param string $protectCode
     * @return Json
     */
    public function createInvoice($orderId, $protectCode);

    /**
     *
     * @param string $orderId
     * @param string $protectCode
     * @return Json
     */
    public function invalidInvoice($orderId, $protectCode);

    /**
     *
     * @param string $orderId
     * @param string $protectCode
     * @return Json
     */
    public function getInvoiceTag($orderId, $protectCode);

    /**
     *
     * @return Json
     */
    public function getInvoiceMainConfig();
}