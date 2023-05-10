<?php
namespace Ecpay\General\Controller\Process;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface ;

use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Foundation\GeneralHelper;

use Ecpay\Sdk\Factories\Factory;

class LogisticMapToEcpay extends Action implements CsrfAwareActionInterface
{
    protected $_loggerInterface;
    protected $_urlInterface;
    protected $_requestInterface;

    protected $_encryptionsService;
    protected $_orderService;
    protected $_mainService;
    protected $_invoiceService;
    protected $_logisticService;
    protected $_paymentService;

    protected $_encryptionsHelper;
    protected $_generalHelper;

    public function __construct(
        LoggerInterface $loggerInterface,
        UrlInterface $urlInterface,
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
        $logisticStage = $this->_mainService->getLogisticConfig('enabled_logistic_stage') ;
        $this->_loggerInterface->debug('LogisticMapToEcpay logisticStage:'. print_r($logisticStage,true));

        // 取出CvsType
        $logisticCvsType = $this->_mainService->getLogisticConfig('logistic_cvs_type') ;
        $this->_loggerInterface->debug('LogisticMapToEcpay logisticCvsType:'. print_r($logisticCvsType,true));

        // 取出 URL
        $apiUrl = $this->_logisticService->getApiUrl('map', $logisticStage, $logisticCvsType);
        $this->_loggerInterface->debug('LogisticMapToEcpay apiUrl:'. print_r($apiUrl,true));

        // 判斷測試模式
        if ($logisticStage == 1){

            // 取出 KEY IV MID (測試模式)
            $accountInfo = $this->_logisticService->getStageAccount($logisticCvsType);
            $this->_loggerInterface->debug('LogisticMapToEcpay accountInfo:'. print_r($accountInfo,true));

        } else {

            // 取出 KEY IV MID (正式模式)
            $logisticMerchantId = $this->_mainService->getLogisticConfig('logistic_mid') ;
            $logisticHashKey    = $this->_mainService->getLogisticConfig('logistic_hashkey') ;
            $logisticHashIv     = $this->_mainService->getLogisticConfig('logistic_hashiv') ;

            $this->_loggerInterface->debug('LogisticMapToEcpay logisticMerchantId:'. print_r($logisticMerchantId,true));
            $this->_loggerInterface->debug('LogisticMapToEcpay logisticHashKey:'. print_r($logisticHashKey,true));
            $this->_loggerInterface->debug('LogisticMapToEcpay logisticHashIv:'. print_r($logisticHashIv,true));

            $accountInfo = [
                'MerchantId' => $logisticMerchantId,
                'HashKey'    => $logisticHashKey,
                'HashIv'     => $logisticHashIv,
            ] ;
        }

        // 解密訂單編號
        $enctyOrderId = $this->getRequest()->getParam('id') ;
        $enctyOrderId = str_replace(' ', '+', $enctyOrderId) ;
        $orderId      = intval($this->_encryptionsService->decrypt($enctyOrderId));

        $this->_loggerInterface->debug('LogisticMapToEcpay enctyOrderId:'. print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('LogisticMapToEcpay orderId:'. print_r($orderId,true));

        // 取出訂單物流方式
        $shippingMethod = $this->_orderService->getShippingMethod($orderId);
        $this->_loggerInterface->debug('LogisticMapToEcpay shippingMethod:'. print_r($shippingMethod,true));

        // 取出訂單前綴
        $logisticOrderPreFix = $this->_mainService->getLogisticConfig('logistic_order_prefix') ;
        $this->_loggerInterface->debug('LogisticMapToEcpay logisticOrderPreFix:'. print_r($logisticOrderPreFix,true));

        // 組合廠商訂單編號
        $merchantTradeNo = $this->_orderService->getMerchantTradeNo($orderId, $logisticOrderPreFix);
        $this->_loggerInterface->debug('LogisticMapToEcpay merchantTradeNo:'. print_r($merchantTradeNo,true));

        // 取出物流子類型
        $logisticsSubType = $this->_logisticService->getCvsLogisticsSubType($logisticCvsType, $shippingMethod);
        $this->_loggerInterface->debug('LogisticMapToEcpay logisticsSubType:'. print_r($logisticsSubType,true));

        // 取出訂單金流方式
        $paymentMethod = $this->_orderService->getPaymentMethod($orderId);
        $this->_loggerInterface->debug('LogisticMapToEcpay paymentMethod:'. print_r($paymentMethod,true));

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

        echo $this->_logisticService->mapToEcpay($accountInfo, $input, $apiUrl);

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