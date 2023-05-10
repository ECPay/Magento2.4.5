<?php

namespace Ecpay\General\Cron;

use Psr\Log\LoggerInterface ;

use Ecpay\General\Model\EcpayLogisticFactory;
use Ecpay\General\Model\EcpayInvoice;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\Sdk\Factories\Factory;

class OrderAutoProcedure
{
    protected $_loggerInterface;
    protected $_urlInterface;

    protected $_ecpayLogisticFactory;

    protected $_encryptionsService;
    protected $_orderService;
    protected $_mainService;
    protected $_invoiceService;
    protected $_logisticService;
    protected $_paymentService;

    public function __construct(
        LoggerInterface $loggerInterface,

        EcpayLogisticFactory $ecpayLogisticFactory,

        EncryptionsService $encryptionsService,
        OrderService $orderService,
        MainService $mainService,
        InvoiceService $invoiceService,
        LogisticService $logisticService,
        PaymentService $paymentService
    ) {
        $this->_loggerInterface = $loggerInterface;

        $this->_ecpayLogisticFactory = $ecpayLogisticFactory;

        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_invoiceService = $invoiceService;
        $this->_logisticService = $logisticService;
        $this->_paymentService = $paymentService;
    }

    public function execute()
    {
        $this->_loggerInterface->debug('-----------------------------ECPay Cron Job OrderAutoProcedure----------------------------------');

        // 取得需要處理的訂單編號

        // 判斷發票是否啟用自動開立
        $ecpayInvoiceAuto = $this->_mainService->getInvoiceConfig('enabled_invoice_auto') ;
        $this->_loggerInterface->debug('OrderAutoProcedure ecpayInvoiceAuto:'. print_r($ecpayInvoiceAuto, true));

        $invoiceOrders = ($ecpayInvoiceAuto == 1) ? $this->_orderService->getOrderForInvoiceAutoProcedure() : [];
        $this->_loggerInterface->debug('OrderAutoProcedure invoiceOrders:' . print_r($invoiceOrders, true));

        // 判斷物流模組是否啟動
        $ecpayEnableLogistic = $this->_mainService->isLogisticModuleEnable();
        $this->_loggerInterface->debug('OrderAutoProcedure ecpayEnableLogistic:'. print_r($ecpayEnableLogistic, true));

        // 判斷物流是否啟用自動開立
        $ecpayLogisticAuto = $this->_mainService->getLogisticConfig('enable_logistic_auto') ;
        $this->_loggerInterface->debug('OrderAutoProcedure ecpayLogisticAuto:'. print_r($ecpayLogisticAuto, true));

        $logisticOrders = ($ecpayEnableLogistic == 1 && $ecpayLogisticAuto == 1) ? $this->_orderService->getOrderForLogisticAutoProcedure() : [];
        $this->_loggerInterface->debug('OrderAutoProcedure logisticOrders:' . print_r($logisticOrders, true));

        // 訂單編號合併
        $orders = array_unique(array_merge($invoiceOrders , $logisticOrders));
        $this->_loggerInterface->debug('OrderAutoProcedure orders:' . print_r($orders, true));

        foreach ($orders as $key => $orderId) {
            $orderId = intval($orderId);
            // 開立發票
            if (in_array($orderId, $invoiceOrders)) {
                $this->invoiceAutoProcess($orderId);
            }
            // 開立物流訂單
            if (in_array($orderId, $logisticOrders)) {
                $this->logisticAutoProcess($orderId);
            }
        }

        return $this;
    }

    /**
     * 自動開立發票程序
     *
     * @param int $orderId
     */
    public function invoiceAutoProcess(int $orderId)
    {
        $this->_loggerInterface->debug('OrderAutoProcedure invoiceAutoProcess orderId:'. print_r($orderId, true));

        // 開立發票
        $result = $this->_invoiceService->invoiceIssue($orderId);
        $this->_loggerInterface->debug('OrderAutoProcedure invoiceIssue result:'. print_r($result, true));

        // 關閉自動開立
        $this->_orderService->setOrderData($orderId, 'ecpay_invoice_auto_tag', 0) ;
        if ($result['code'] !== '0999') {
            // 回傳結果寫入備註
            $comment = '自動開立發票訂單(失敗)，請重新手動開立。錯誤代碼：' . $result['code'];
            $status = false ;
            $isVisibleOnFront = false ;
            $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront) ;
        }
    }

    /**
     * 自動產生物流單程序
     *
     * @param int $orderId
     */
    public function logisticAutoProcess(int $orderId)
    {
        $this->_loggerInterface->debug('OrderAutoProcedure logisticAutoProcess orderId : '. print_r($orderId, true));

        // 建立物流訂單
        $result = $this->_logisticService->logisticCreateOrder($orderId);
        $this->_loggerInterface->debug('OrderAutoProcedure logisticCreateOrder result:' . print_r($result, true));

        // 關閉自動開立
        $this->_orderService->setOrderData($orderId, 'ecpay_logistic_auto_tag', 0) ;
        if ($result['code'] !== '0999') {
            // 回傳結果寫入備註
            $comment = '自動建立物流訂單(失敗)，請重新手動建立。錯誤代碼：' . $result['code'];
            $status = false ;
            $isVisibleOnFront = false ;
            $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront) ;
        }
    }
}