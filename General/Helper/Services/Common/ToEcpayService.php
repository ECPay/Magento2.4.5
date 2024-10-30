<?php
namespace Ecpay\General\Helper\Services\Common;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\MainService;

class ToEcpayService extends AbstractHelper
{
    protected $_loggerInterface;
    protected $_urlInterface;
    protected $_mainService;
    protected $_orderService;
    protected $_paymentService;
    protected $_logisticService;
    protected $_encryptionsService;

    /**
     * @param Context $context
     */
    public function __construct(
        LoggerInterface $loggerInterface,
        UrlInterface $urlInterface,
        MainService $mainService,
        OrderService $orderService,
        PaymentService $paymentService,
        LogisticService $logisticService,
        EncryptionsService $encryptionsService
    )
    {
        $this->_loggerInterface = $loggerInterface;
        $this->_urlInterface = $urlInterface;
        $this->_mainService = $mainService;
        $this->_orderService = $orderService;
        $this->_paymentService = $paymentService;
        $this->_logisticService = $logisticService;
        $this->_encryptionsService = $encryptionsService;
    }

    /**
     * 組合送往綠界物流格式
     */
    public function prepareLogistic($encOrderId) {
        // 取出是否為測試模式
        $logisticStage = $this->_mainService->getLogisticConfig('enabled_logistic_stage') ;
        $this->_loggerInterface->debug('ToEcpayService prepareLogistic logisticStage:'. print_r($logisticStage,true));

        // 取出CvsType
        $logisticCvsType = $this->_mainService->getLogisticConfig('logistic_cvs_type') ;
        $this->_loggerInterface->debug('ToEcpayService prepareLogistic logisticCvsType:'. print_r($logisticCvsType,true));

        // 取出 URL
        $apiUrl = $this->_logisticService->getApiUrl('map', $logisticStage, $logisticCvsType);
        $this->_loggerInterface->debug('ToEcpayService prepareLogistic apiUrl:'. print_r($apiUrl,true));

        // 判斷測試模式
        if ($logisticStage == 1){

            // 取出 KEY IV MID (測試模式)
            $accountInfo = $this->_logisticService->getStageAccount($logisticCvsType);
            $this->_loggerInterface->debug('ToEcpayService prepareLogistic accountInfo:'. print_r($accountInfo,true));

        } else {

            // 取出 KEY IV MID (正式模式)
            $logisticMerchantId = $this->_mainService->getLogisticConfig('logistic_mid') ;
            $logisticHashKey    = $this->_mainService->getLogisticConfig('logistic_hashkey') ;
            $logisticHashIv     = $this->_mainService->getLogisticConfig('logistic_hashiv') ;

            $this->_loggerInterface->debug('ToEcpayService prepareLogistic logisticMerchantId:'. print_r($logisticMerchantId,true));
            $this->_loggerInterface->debug('ToEcpayService prepareLogistic logisticHashKey:'. print_r($logisticHashKey,true));
            $this->_loggerInterface->debug('ToEcpayService prepareLogistic logisticHashIv:'. print_r($logisticHashIv,true));

            $accountInfo = [
                'MerchantId' => $logisticMerchantId,
                'HashKey'    => $logisticHashKey,
                'HashIv'     => $logisticHashIv,
            ] ;
        }

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $encOrderId) ;
        $orderId      = intval($this->_encryptionsService->decrypt($enctyOrderId));

        $this->_loggerInterface->debug('ToEcpayService prepareLogistic enctyOrderId:'. print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('ToEcpayService prepareLogistic orderId:'. print_r($orderId,true));

        // 取出訂單物流方式
        $shippingMethod = $this->_orderService->getShippingMethod($orderId);
        $this->_loggerInterface->debug('ToEcpayService prepareLogistic shippingMethod:'. print_r($shippingMethod,true));

        // 取出訂單前綴
        $logisticOrderPreFix = $this->_mainService->getLogisticConfig('logistic_order_prefix') ;
        $this->_loggerInterface->debug('ToEcpayService prepareLogistic logisticOrderPreFix:'. print_r($logisticOrderPreFix,true));

        // 組合廠商訂單編號
        $merchantTradeNo = $this->_orderService->getMerchantTradeNo($orderId, $logisticOrderPreFix);
        $this->_loggerInterface->debug('ToEcpayService prepareLogistic merchantTradeNo:'. print_r($merchantTradeNo,true));

        // 取出物流子類型
        $logisticsSubType = $this->_logisticService->getCvsLogisticsSubType($logisticCvsType, $shippingMethod);
        $this->_loggerInterface->debug('ToEcpayService prepareLogistic logisticsSubType:'. print_r($logisticsSubType,true));

        // 取出訂單金流方式
        $paymentMethod = $this->_orderService->getPaymentMethod($orderId);
        $this->_loggerInterface->debug('ToEcpayService prepareLogistic paymentMethod:'. print_r($paymentMethod,true));

        // 判斷是否為貨到付款
        $isCollection = ($paymentMethod == 'cashondelivery') ? 'Y' : 'N' ;

        // 回傳門市資訊網址
        $serverReplyURL = $this->_urlInterface->getUrl('ecpaygeneral/Process/LogisticMapResponse');
        $serverReplyURL = $serverReplyURL . '?id='. $enctyOrderId ;
        $this->_loggerInterface->debug('MapToEcpay $serverReplyURL:'. print_r($serverReplyURL,true));

        // 組合門市選擇API
        $input = [
            'merchantId'        => $accountInfo['MerchantId'],
            'merchantTradeNo'   => $merchantTradeNo,
            'logisticsSubType'  => $logisticsSubType,
            'isCollection'      => $isCollection,
            'serverReplyURL'    => $serverReplyURL,
        ];

        return $this->_logisticService->mapToEcpay($accountInfo, $input, $apiUrl);
    }

    /**
     * 組合送往綠界金流格式
     */
    public function preparePayment($enctyOrderId) {
        // 取出是否為測試模式
        $paymentStage = $this->_mainService->getPaymentConfig('enabled_payment_stage');
        $this->_loggerInterface->debug('ToEcpayService preparePayment paymentStage:'. print_r($paymentStage,true));

        // 取出 URL
        $apiUrl = $this->_paymentService->getApiUrl('check_out', $paymentStage);
        $this->_loggerInterface->debug('ToEcpayService preparePayment apiUrl:'. print_r($apiUrl,true));

        // 判斷測試模式
        if ($paymentStage == 1) {

            // 取出 KEY IV MID (測試模式)
            $accountInfo = $this->_paymentService->getStageAccount();
            $this->_loggerInterface->debug('ToEcpayService preparePayment accountInfo:'. print_r($accountInfo,true));

        } else {

            // 取出 KEY IV MID (正式模式)
            $paymentMerchantId = $this->_mainService->getPaymentConfig('payment_mid');
            $paymentHashKey    = $this->_mainService->getPaymentConfig('payment_hashkey');
            $paymentHashIv     = $this->_mainService->getPaymentConfig('payment_hashiv');

            $this->_loggerInterface->debug('ToEcpayService preparePayment paymentMerchantId:'. print_r($paymentMerchantId,true));
            $this->_loggerInterface->debug('ToEcpayService preparePayment paymentHashKey:'. print_r($paymentHashKey,true));
            $this->_loggerInterface->debug('ToEcpayService preparePayment paymentHashIv:'. print_r($paymentHashIv,true));

            $accountInfo = [
                'MerchantId' => $paymentMerchantId,
                'HashKey'    => $paymentHashKey,
                'HashIv'     => $paymentHashIv,
            ];
        }

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $enctyOrderId);
        $orderId      = (int) $this->_encryptionsService->decrypt($enctyOrderId);

        $this->_loggerInterface->debug('ToEcpayService preparePayment enctyOrderId:'. print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('ToEcpayService preparePayment orderId:'. print_r($orderId,true));

        // 取出訂單金流方式
        $paymentMethod = $this->_orderService->getPaymentMethod($orderId);
        $this->_loggerInterface->debug('ToEcpayService preparePayment paymentMethod:'. print_r($paymentMethod,true));

        // 取出訂單前綴
        $paymentOrderPreFix = $this->_mainService->getPaymentConfig('payment_order_prefix');
        $this->_loggerInterface->debug('ToEcpayService preparePayment paymentOrderPreFix:'. print_r($paymentOrderPreFix,true));

        // 組合廠商訂單編號
        $merchantTradeNo = $this->_orderService->getMerchantTradeNo($orderId, $paymentOrderPreFix);
        $this->_loggerInterface->debug('ToEcpayService preparePayment merchantTradeNo:'. print_r($merchantTradeNo,true));

        // 訂單金額
        $totalAmount = (int) ceil($this->_orderService->getGrandTotal($orderId));
        $this->_loggerInterface->debug('ToEcpayService preparePayment $totalAmount:'. print_r($totalAmount,true));

        // 綠界訂單是否顯示商品名稱
        $paymentDispItemName = $this->_mainService->getPaymentConfig('enabled_payment_disp_item_name');
        $this->_loggerInterface->debug('ToEcpayService preparePayment paymentDispItemName:'. print_r($paymentDispItemName,true));

        $itemNameDefault = __('A Package Of Online Goods');

        if ( $paymentDispItemName == 1 ) {
            // 取出訂單品項
            $salesOrderItem = $this->_orderService->getSalesOrderItemByOrderId($orderId);
            $this->_loggerInterface->debug('ToEcpayService preparePayment salesOrderItem:'. print_r($salesOrderItem,true));

            // 轉換商品名稱格式
            $itemName = $this->_paymentService->convertToPaymentItemName($salesOrderItem);
            $this->_loggerInterface->debug('ToEcpayService preparePayment itemName:'. print_r($itemName,true));

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
        $this->_loggerInterface->debug('ToEcpayService preparePayment itemName:'. print_r($itemName,true));

        // 回傳資訊網址 - PaymentInfoURL
        $paymentInfoURL = $this->_urlInterface->getUrl('ecpaygeneral/Process/PaymentInfoResponse');
        $paymentInfoURL = $paymentInfoURL . '?id='. $enctyOrderId;
        $this->_loggerInterface->debug('ToEcpayService preparePayment $paymentInfoURL:'. print_r($paymentInfoURL,true));

        // 回傳資訊網址 - ReturnURL
        $returnURL = $this->_urlInterface->getUrl('ecpaygeneral/Process/PaymentResponse');
        $returnURL = $returnURL . '?id='. $enctyOrderId;
        $this->_loggerInterface->debug('ToEcpayService preparePayment $returnURL:'. print_r($returnURL,true));

        $clientBackURL = $this->_urlInterface->getUrl('ecpaygeneral/Page/ThankYou');
        $clientBackURL = $clientBackURL . '?id='. $enctyOrderId;
        $this->_loggerInterface->debug('ToEcpayService preparePayment $clientBackURL:'. print_r($clientBackURL,true));

        // 送出前異動訂單狀態
        $this->_orderService->setOrderState($orderId, Order::STATE_PENDING_PAYMENT);
        $this->_orderService->setOrderStatus($orderId, Order::STATE_PENDING_PAYMENT);

        // 取得額外參數
        $additionalInformation = $this->_orderService->getAdditionalInformation($orderId);
        $this->_loggerInterface->debug('ToEcpayService preparePayment $additionalInformation:'. print_r($additionalInformation,true));

        // 備註及儲存金流廠商訂單編號
        $comment = sprintf(__('ECPay Payment, MerchantTradeNo :%s'), $merchantTradeNo);
        $this->_orderService->setOrderCommentForBack($orderId, $comment);
        $this->_orderService->setOrderData($orderId, 'ecpay_payment_merchant_trade_no', $merchantTradeNo) ;

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

        return $this->_paymentService->checkout($accountInfo, $input, $apiUrl);
    }
}