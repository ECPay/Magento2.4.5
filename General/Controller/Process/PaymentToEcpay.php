<?php
namespace Ecpay\General\Controller\Process;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Foundation\GeneralHelper;

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Services\UrlService;

class PaymentToEcpay extends Action implements CsrfAwareActionInterface
{
    protected $_urlInterface;
    protected $_loggerInterface;
    protected $_requestInterface;

    protected $_encryptionsService;
    protected $_orderService;
    protected $_mainService;
    protected $_invoiceService;
    protected $_logisticService;
    protected $_paymentService;

    protected $_generalHelper;

    public function __construct(
        UrlInterface $urlInterface,
        LoggerInterface $loggerInterface,
        Context $context,
        RequestInterface $requestInterface,

        EncryptionsService $encryptionsService,
        OrderService $orderService,
        MainService $mainService,
        InvoiceService $invoiceService,
        LogisticService $logisticService,
        PaymentService $paymentService,

        GeneralHelper $generalHelper
    )
    {
        $this->_loggerInterface = $loggerInterface;
        $this->_urlInterface = $urlInterface;
        $this->_requestInterface = $requestInterface;

        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_invoiceService = $invoiceService;
        $this->_logisticService = $logisticService;
        $this->_paymentService = $paymentService;

        $this->_generalHelper = $generalHelper;

        return parent::__construct($context);
    }

    public function execute()
    {
        // 取出是否為測試模式
        $paymentStage = $this->_mainService->getPaymentConfig('enabled_payment_stage');
        $this->_loggerInterface->debug('PaymentToEcpay paymentStage:'. print_r($paymentStage,true));

        // 取出 URL
        $apiUrl = $this->_paymentService->getApiUrl('check_out', $paymentStage);
        $this->_loggerInterface->debug('PaymentToEcpay apiUrl:'. print_r($apiUrl,true));

        // 判斷測試模式
        if ($paymentStage == 1) {

            // 取出 KEY IV MID (測試模式)
            $accountInfo = $this->_paymentService->getStageAccount();
            $this->_loggerInterface->debug('PaymentToEcpay accountInfo:'. print_r($accountInfo,true));

        } else {

            // 取出 KEY IV MID (正式模式)
            $paymentMerchantId = $this->_mainService->getPaymentConfig('payment_mid');
            $paymentHashKey    = $this->_mainService->getPaymentConfig('payment_hashkey');
            $paymentHashIv     = $this->_mainService->getPaymentConfig('payment_hashiv');

            $this->_loggerInterface->debug('PaymentToEcpay paymentMerchantId:'. print_r($paymentMerchantId,true));
            $this->_loggerInterface->debug('PaymentToEcpay paymentHashKey:'. print_r($paymentHashKey,true));
            $this->_loggerInterface->debug('PaymentToEcpay paymentHashIv:'. print_r($paymentHashIv,true));

            $accountInfo = [
                'MerchantId' => $paymentMerchantId,
                'HashKey'    => $paymentHashKey,
                'HashIv'     => $paymentHashIv,
            ];
        }

        // 解密訂單編號
        $enctyOrderId = $this->getRequest()->getParam('id');
        $enctyOrderId = str_replace(' ', '+', $enctyOrderId);
        $orderId      = (int) $this->_encryptionsService->decrypt($enctyOrderId);

        $this->_loggerInterface->debug('PaymentToEcpay enctyOrderId:'. print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('PaymentToEcpay orderId:'. print_r($orderId,true));

        // 取出訂單金流方式
        $paymentMethod = $this->_orderService->getPaymentMethod($orderId);
        $this->_loggerInterface->debug('PaymentToEcpay paymentMethod:'. print_r($paymentMethod,true));

        // 取出訂單前綴
        $paymentOrderPreFix = $this->_mainService->getPaymentConfig('payment_order_prefix');
        $this->_loggerInterface->debug('PaymentToEcpay paymentOrderPreFix:'. print_r($paymentOrderPreFix,true));

        // 組合廠商訂單編號
        $merchantTradeNo = $this->_orderService->getMerchantTradeNo($orderId, $paymentOrderPreFix);
        $this->_loggerInterface->debug('PaymentToEcpay merchantTradeNo:'. print_r($merchantTradeNo,true));

        // 訂單金額
        $totalAmount = (int) ceil($this->_orderService->getGrandTotal($orderId));
        $this->_loggerInterface->debug('PaymentToEcpay $totalAmount:'. print_r($totalAmount,true));

        // 綠界訂單是否顯示商品名稱
        $paymentDispItemName = $this->_mainService->getPaymentConfig('enabled_payment_disp_item_name');
        $this->_loggerInterface->debug('PaymentToEcpay paymentDispItemName:'. print_r($paymentDispItemName,true));

        $itemNameDefault = __('A Package Of Online Goods');

        if ( $paymentDispItemName == 1 ) {
            // 取出訂單品項
            $salesOrderItem = $this->_orderService->getSalesOrderItemByOrderId($orderId);
            $this->_loggerInterface->debug('PaymentToEcpay salesOrderItem:'. print_r($salesOrderItem,true));

            // 轉換商品名稱格式
            $itemName = $this->_paymentService->convertToPaymentItemName($salesOrderItem);
            $this->_loggerInterface->debug('PaymentToEcpay itemName:'. print_r($itemName,true));

            // 判斷是否超過長度，如果超過長度改為預設文字
            if (strlen($itemName) > 400) {

                $itemName = $itemNameDefault;

                // 寫入備註
                $comment = '商品名稱超過綠界金流可允許長度強制改為:' . $itemName;
                $this->_orderService->setOrderCommentForBack($orderId, $comment);
            }
        } else {
            $itemName = $itemNameDefault;
        }
        $this->_loggerInterface->debug('PaymentToEcpay itemName:'. print_r($itemName,true));

        // 回傳資訊網址 - PaymentInfoURL
        $paymentInfoURL = $this->_urlInterface->getUrl('ecpaygeneral/Process/PaymentInfoResponse');
        $paymentInfoURL = $paymentInfoURL . '?id='. $enctyOrderId;
        $this->_loggerInterface->debug('PaymentToEcpay $paymentInfoURL:'. print_r($paymentInfoURL,true));

        // 回傳資訊網址 - ReturnURL
        $returnURL = $this->_urlInterface->getUrl('ecpaygeneral/Process/PaymentResponse');
        $returnURL = $returnURL . '?id='. $enctyOrderId;
        $this->_loggerInterface->debug('PaymentToEcpay $returnURL:'. print_r($returnURL,true));

        $clientBackURL = $this->_urlInterface->getUrl('ecpaygeneral/Page/ThankYou');
        $clientBackURL = $clientBackURL . '?id='. $enctyOrderId;
        $this->_loggerInterface->debug('PaymentToEcpay $clientBackURL:'. print_r($clientBackURL,true));

        // 送出前異動訂單狀態
        $this->_orderService->setOrderState($orderId, Order::STATE_PENDING_PAYMENT);
        $this->_orderService->setOrderStatus($orderId, Order::STATE_PENDING_PAYMENT);

        // 取得額外參數
        $additionalInformation = $this->_orderService->getAdditionalInformation($orderId);
        $this->_loggerInterface->debug('PaymentToEcpay $additionalInformation:'. print_r($additionalInformation,true));

        $comment = sprintf(__('ECPay Payment, MerchantTradeNo :%s'), $merchantTradeNo);
        $this->_orderService->setOrderCommentForBack($orderId, $comment);

        $input = [
            'enctyOrderId'          => $enctyOrderId,
            'paymentMethod'         => $paymentMethod,
            'merchantId'            => $accountInfo['MerchantId'],
            'merchantTradeNo'       => $merchantTradeNo,
            'totalAmount'           => $totalAmount,
            'itemName'              => $itemName,
            'returnUrl'             => $returnURL,
            'clientBackUrl'         => $clientBackURL,
            'orderResultUrl'        => $returnURL,
            'paymentInfoUrl'        => $paymentInfoURL,
            'additionalInformation' => $additionalInformation
        ];

        echo $this->_paymentService->checkout($accountInfo, $input, $apiUrl);

        exit();
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $requestInterface
    ): ?InvalidRequestException {

        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $requestInterface): ?bool
    {
        return true;
    }
}