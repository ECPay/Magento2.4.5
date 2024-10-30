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

class LogisticChangeStoreResponse extends Action implements CsrfAwareActionInterface
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
        // 接收門市資訊
        $storeInfo = $this->_requestInterface->getPostValue();
        $this->_loggerInterface->debug('LogisticChangeStoreResponse storeInfo:'. print_r($storeInfo,true));

        // 解密訂單編號
        $enctyOrderId = str_replace(' ', '+', $storeInfo['ExtraData']) ;
        $orderId      = $this->_encryptionsService->decrypt($enctyOrderId);
        $orderId = (int) $orderId;

        $this->_loggerInterface->debug('LogisticChangeStoreResponse enctyOrderId:'. print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('LogisticChangeStoreResponse orderId:'. print_r($orderId,true));

        // 判斷訂單是否允許更改

        // 物流單建立狀態
        $ecpayShippingTag = $this->_orderService->getEcpayShippingTag($orderId);
        $this->_loggerInterface->debug('LogisticChangeStoreResponse $ecpayShippingTag:'.$ecpayShippingTag);

        // 目前僅支援一張物流單
        if($ecpayShippingTag == 1){
            $response = $this->_responseFactory->create();
            $response->setHttpResponseCode(200); // 設置 HTTP 狀態碼
            $response->setBody('訂單已經建立，不允許修改門市'); // 設置回應的內容
            return $response->sendResponse();
        }

        // 更新資料庫
        $CVSStoreID     = isset($storeInfo['CVSStoreID']) ? $storeInfo['CVSStoreID'] : '';
        $CVSStoreName   = isset($storeInfo['CVSStoreName']) ? $storeInfo['CVSStoreName'] : '';
        $CVSAddress     = isset($storeInfo['CVSAddress']) ? $storeInfo['CVSAddress'] : '';
        $CVSTelephone   = isset($storeInfo['CVSTelephone']) ? $storeInfo['CVSTelephone'] : '';

        if(!empty($CVSStoreID)){

            $this->_orderService->setOrderData($orderId, 'ecpay_logistic_cvs_store_id', $CVSStoreID) ;
            $this->_orderService->setOrderData($orderId, 'ecpay_logistic_cvs_store_name', $CVSStoreName) ;
            $this->_orderService->setOrderData($orderId, 'ecpay_logistic_cvs_store_address', $CVSAddress) ;
            $this->_orderService->setOrderData($orderId, 'ecpay_logistic_cvs_store_telephone', $CVSTelephone) ;

            // todo:異動物流地址
            
            // 備註
            $comment = '後台更改門市資訊(成功)，超商店舖名稱：'. $CVSStoreName . '，店舖編號：' . $CVSStoreID . '，店舖地址：' . $CVSAddress; 
            $response = $this->_responseFactory->create();
            $response->setHttpResponseCode(200); // 設置 HTTP 狀態碼
            $response->setBody('更改門市成功，請重新整理訂單頁面<br>超商名稱：' . $CVSStoreName . '<br>超商編號：' . $CVSStoreID . '<br>超商地址：' . $CVSAddress . '<br>'); // 設置回應的內容
            return $response->sendResponse();

        } else {
            // 備註
            $comment = '後台更改門市資訊(失敗):門市不存在' ; 
            $response = $this->_responseFactory->create();
            $response->setHttpResponseCode(200); // 設置 HTTP 狀態碼
            $response->setBody('門市不存在'); // 設置回應的內容
            return $response->sendResponse();
        }
        
        $status = false ;
        $isVisibleOnFront = false ;

        $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront) ;
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