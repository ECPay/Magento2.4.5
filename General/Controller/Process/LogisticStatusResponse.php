<?php
namespace Ecpay\General\Controller\Process;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface ;

use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;

use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\Sdk\Response\VerifiedArrayResponse;

class LogisticStatusResponse extends Action implements CsrfAwareActionInterface
{
    protected $_requestInterface;
    protected $_loggerInterface;
    protected $_urlInterface;
    protected $_responseFactory;

    protected $_encryptionsService;
    protected $_orderService;
    protected $_mainService;
    protected $_invoiceService;
    protected $_logisticService;
    protected $_paymentService;

    public function __construct(
        Context $context,
        RequestInterface $requestInterface,
        LoggerInterface $loggerInterface,
        UrlInterface $urlInterface,
        ResponseFactory $responseFactory,

        EncryptionsService $encryptionsService,
        OrderService $orderService,
        MainService $mainService,
        InvoiceService $invoiceService,
        LogisticService $logisticService,
        PaymentService $paymentService
    )
    {
        $this->_requestInterface = $requestInterface;
        $this->_loggerInterface = $loggerInterface;
        $this->_urlInterface = $urlInterface;
        $this->_responseFactory = $responseFactory;

        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_invoiceService = $invoiceService;
        $this->_logisticService = $logisticService;
        $this->_paymentService = $paymentService;

        return parent::__construct($context);
    }

    public function execute()
    {
        // 取出是否為測試模式
        $logisticStage = $this->_mainService->getLogisticConfig('enabled_logistic_stage') ;
        $this->_loggerInterface->debug('LogisticStatusResponse logisticStage:'. print_r($logisticStage,true));

         // 取出CvsType
        $logisticCvsType = $this->_mainService->getLogisticConfig('logistic_cvs_type') ;
        $this->_loggerInterface->debug('LogisticStatusResponse logisticCvsType:'. print_r($logisticCvsType,true));

        // 判斷測試模式
        if($logisticStage == 1){

            // 取出 KEY IV MID (測試模式)
            $accountInfo = $this->_logisticService->getStageAccount($logisticCvsType);
            $this->_loggerInterface->debug('LogisticStatusResponse accountInfo:'. print_r($accountInfo,true));

        } else {

            // 取出 KEY IV MID (正式模式)
            $logisticMerchantId = $this->_mainService->getLogisticConfig('logistic_mid') ;
            $logisticHashKey    = $this->_mainService->getLogisticConfig('logistic_hashkey') ;
            $logisticHashIv     = $this->_mainService->getLogisticConfig('logistic_hashiv') ;

            $this->_loggerInterface->debug('LogisticStatusResponse logisticMerchantId:'. print_r($logisticMerchantId,true));
            $this->_loggerInterface->debug('LogisticStatusResponse logisticHashKey:'. print_r($logisticHashKey,true));
            $this->_loggerInterface->debug('LogisticStatusResponse logisticHashIv:'. print_r($logisticHashIv,true));

            $accountInfo = [
                'MerchantId' => $logisticMerchantId,
                'HashKey'    => $logisticHashKey,
                'HashIv'     => $logisticHashIv,
            ] ;
        }

        // 透過SDK取回
        try {
            
            $factory = new Factory([
                'hashKey'       => $accountInfo['HashKey'],
                'hashIv'        => $accountInfo['HashIv'],
                'hashMethod'    => 'md5',
            ]);
            
            $checkoutResponse = $factory->create(VerifiedArrayResponse::class);
            $resposeInfo = $checkoutResponse->get($_POST);
            $this->_loggerInterface->debug('LogisticStatusResponse resposeInfo:'. print_r($resposeInfo,true));

            if(isset($resposeInfo['RtnCode'])){

                // 透過MerchantTradeNo取出OrderId
                $orderInfo = $this->_orderService->getEcpayLogisticInfoByMerchantTradeNo($resposeInfo['MerchantTradeNo']);
                $this->_loggerInterface->debug('LogisticStatusResponse orderInfo:'. print_r($orderInfo,true));

                if(isset($orderInfo['order_id'])){

                    // 回傳結果寫入備註
                    $orderId = (int) $orderInfo['order_id'];
                    $comment = '貨態回傳，交易單號：' . $resposeInfo['MerchantTradeNo'] . '，狀態：' . $resposeInfo['RtnMsg'] . '('. $resposeInfo['RtnCode'] . ')，綠界科技的物流交易編號：' . $resposeInfo['AllPayLogisticsID']; 
                    $status = false;
                    $isVisibleOnFront = false;

                    $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);

                    echo '1|OK';
                }  
            }

        } catch (RtnException $e) {
            $this->_loggerInterface->debug('LogisticStatusResponse resposeInfo:'. print_r('(' . $e->getCode() . ')' . $e->getMessage()));
        }

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