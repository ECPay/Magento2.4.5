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

class InvoiceDelayNotifyResponse extends Action implements CsrfAwareActionInterface
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
        
        $invoiceInfo = $this->_requestInterface->getPostValue();
        $this->_loggerInterface->debug('InvoiceDelayNotifyResponse invoiceInfo:'. print_r($invoiceInfo,true));

        // 利用od_sob解析出訂單編號
        if(isset($invoiceInfo['od_sob']) && isset($invoiceInfo['tsr']) && !empty($invoiceInfo['tsr'])){

            // 取出訂單資訊
            $orderInfo = $this->_orderService->getOrderByEcpayInvoiceOdSob($invoiceInfo['od_sob']);
            $this->_loggerInterface->debug('InvoiceDelayNotifyResponse orderInfo:'. print_r($orderInfo,true));

            // 寫入訂單對應欄位
            if(isset($orderInfo[0]['entity_id'])){

                $orderId = (int) $orderInfo[0]['entity_id'];

                $this->_orderService->setOrderData($orderId, 'ecpay_invoice_number', $invoiceInfo['invoicenumber']) ;

                $InvoiceDate = $invoiceInfo['invoicedate'] .' '. $invoiceInfo['invoicetime'] ;
                $this->_orderService->setOrderData($orderId, 'ecpay_invoice_date', $InvoiceDate) ;

                $this->_orderService->setOrderData($orderId, 'ecpay_invoice_random_number', $invoiceInfo['invoicecode']) ;

                // 回傳資料寫入備註
                $comment = '延遲開立成功，發票號碼：' . $invoiceInfo['invoicenumber'] . '，檢查碼：' . $invoiceInfo['invoicecode'] . '，交易單號：'. $invoiceInfo['od_sob']; 
                $status = false ;
                $isVisibleOnFront = false ;

                $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront) ;

                // 回傳綠界成功狀態
                echo '1|OK';  
            }
            
        } else {

            echo '0|Fail';
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