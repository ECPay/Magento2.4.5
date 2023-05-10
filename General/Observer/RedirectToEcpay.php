<?php

namespace Ecpay\General\Observer;

use Psr\Log\LoggerInterface ;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Ecpay\General\Model\EcpayInvoice;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Services\Config\LogisticService;
class RedirectToEcpay implements ObserverInterface
{
    protected $_loggerInterface;
    protected $_urlInterface;
    protected $_responseFactory;

    protected $_encryptionsService;
    protected $_orderService;

    protected $_mainService;
    protected $_paymentService;
    protected $_logisticService;

    protected $_generalHelper;

    public function __construct(
        LoggerInterface $loggerInterface,
        UrlInterface $urlInterface,
        ResponseFactory $responseFactory,
        EncryptionsService $encryptionsService,
        OrderService $orderService,
        MainService $mainService,
        PaymentService $paymentService,
        LogisticService $logisticService,
        GeneralHelper $generalHelper
    ) {
        $this->_loggerInterface = $loggerInterface;
        $this->_urlInterface = $urlInterface;
        $this->_responseFactory = $responseFactory;
        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_paymentService = $paymentService;
        $this->_logisticService = $logisticService;
        $this->_generalHelper = $generalHelper;
    }

    public function execute(Observer $observer)
    {
        // 取出資料
        $orderIdTmp = $observer->getData('order_ids');
        $orderId = intval($orderIdTmp[0]);

        $order = $observer->getData('order');
        $this->_loggerInterface->debug('RedirectProcess Event $result:');

        // logging to test overrid
        $this->_loggerInterface->debug('RedirectProcess Event $orderId:'. print_r($orderId,true));

        // 取出物流方式
        $shippingMethod = $order->getShippingMethod();
        $this->_loggerInterface->debug('RedirectProcess Event $shippingMethod:'.$shippingMethod);

        // 取出金流方式
        $payment = $order->getPayment();
        $paymentMethod = $payment->getMethod();
        $this->_loggerInterface->debug('RedirectProcess Event $paymentMethod:'.$paymentMethod);

         // 訂單編號加密
        $encOrderId = $this->_encryptionsService->encrypt($orderId);
        $this->_loggerInterface->debug('RedirectProcess Event $orderId:'. print_r($orderId,true));
        $this->_loggerInterface->debug('RedirectProcess Event $encOrderId:'. print_r($encOrderId,true));

        // 判斷是否為綠界金流
        $isEcpayPyment = $this->_paymentService->isEcpayPayment($paymentMethod) ;

        // 判斷是否為綠界物流
        $isEcpayLogistic = $this->_logisticService->isEcpayLogistics($shippingMethod) ;

        // 判斷綠界的物流類型
        if ($isEcpayLogistic) {

            if ($this->_logisticService->isEcpayCvsLogistics($shippingMethod)) {
                $cvsOrHomeCheck = 'cvs' ;
            } else {
                $cvsOrHomeCheck = 'home' ;
            }
        }

        if ($isEcpayLogistic) {

            if ($cvsOrHomeCheck == 'cvs') {

                // 超商取貨
                // 轉導產生地圖FORM -> MapToEcpay.php
                $redirectUrl = $this->_urlInterface->getUrl("ecpaygeneral/Process/LogisticMapToEcpay");
                $redirectUrl = $redirectUrl . '?id='. $encOrderId ;

                $this->_loggerInterface->debug('RedirectProcess Event $redirectUrl:'. print_r($redirectUrl,true));
                $this->_responseFactory->create()->setRedirect($redirectUrl)->sendResponse();

            } elseif($cvsOrHomeCheck == 'home') {

                // 宅配
                // 判斷是否為綠界金流
                if ($isEcpayPyment) {
                    $this->paymentToEcpay($orderId);
                }
            }

        } else {

            // 判斷是否為綠界金流
            if ($isEcpayPyment) {
                $this->paymentToEcpay($orderId);
            } else {
                // 判斷是否使用綠界發票
                $invoiceType = $this->_orderService->getecpayInvoiceType($orderId);
                $this->_loggerInterface->debug('RedirectProcess Event $invoiceType:'. print_r($invoiceType,true));

                if (in_array($invoiceType, [EcpayInvoice::ECPAY_INVOICE_TYPE_C, EcpayInvoice::ECPAY_INVOICE_TYPE_D, EcpayInvoice::ECPAY_INVOICE_TYPE_P])) {
                    // 判斷發票模組是否啟動
                    $ecpayEnableInvoice = $this->_mainService->isInvoiceModuleEnable();
                    $this->_loggerInterface->debug('RedirectProcess ecpayEnableInvoice:'. print_r($ecpayEnableInvoice,true));

                    // 判斷發票是否啟用自動開立
                    $ecpayInvoiceAuto = $this->_mainService->getInvoiceConfig('enabled_invoice_auto') ;
                    $this->_loggerInterface->debug('RedirectProcess ecpayInvoiceAuto:'. print_r($ecpayInvoiceAuto,true));

                    if ($ecpayEnableInvoice == 1 && $ecpayInvoiceAuto == 1) {
                        $this->_orderService->setOrderData($orderId, 'ecpay_invoice_auto_tag', 1) ;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * 轉導到綠界AIO
     *
     * @return void
     */
    private function paymentToEcpay(string $orderId)
    {
        // 訂單編號加密
        $encOrderId = $this->_encryptionsService->encrypt($orderId);

        // 轉導到綠界金流執行程序組合FORM(帶ORDER_ID走) -> PaymentToEcpay.php
        $redirectUrl = $this->_urlInterface->getUrl('ecpaygeneral/Process/PaymentToEcpay');
        $redirectUrl = $redirectUrl . '?id='. $encOrderId ;

        $this->_loggerInterface->debug('RedirectProcess Event $redirectUrl:'. print_r($redirectUrl,true));
        $this->_responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
    }
}