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

class LogisticMapResponse extends Action implements CsrfAwareActionInterface
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
        $this->_loggerInterface->debug('MapResponse storeInfo:'. print_r($storeInfo,true));

        // 解密訂單編號
        $enctyOrderId = $this->getRequest()->getParam('id') ;
        $enctyOrderId = str_replace(' ', '+', $enctyOrderId) ;
        $orderId      = intval($this->_encryptionsService->decrypt($enctyOrderId));

        $this->_loggerInterface->debug('MapResponse enctyOrderId:'. print_r($enctyOrderId,true));
        $this->_loggerInterface->debug('MapResponse orderId:'. print_r($orderId,true));

        // 驗證訂單資訊 (驗證物流方式)
        $shippingMethod = $this->_orderService->getShippingMethod($orderId);
        $this->_loggerInterface->debug('MapResponse shippingMethod:'. print_r($shippingMethod,true));

        if ($this->_logisticService->isEcpayCvsLogistics($shippingMethod)) {

            // 門市資訊寫入資料庫
            $CVSStoreID   = isset($storeInfo['CVSStoreID']) ? $storeInfo['CVSStoreID'] : '';
            $CVSStoreName = isset($storeInfo['CVSStoreName']) ? $storeInfo['CVSStoreName'] : '';
            $CVSAddress   = isset($storeInfo['CVSAddress']) ? $storeInfo['CVSAddress'] : '';
            $CVSTelephone = isset($storeInfo['CVSTelephone']) ? $storeInfo['CVSTelephone'] : '';

            if (!empty($CVSStoreID)) {
                $this->_orderService->setOrderData($orderId, 'ecpay_logistic_cvs_store_id', $CVSStoreID) ;
                $this->_orderService->setOrderData($orderId, 'ecpay_logistic_cvs_store_name', $CVSStoreName) ;
                $this->_orderService->setOrderData($orderId, 'ecpay_logistic_cvs_store_address', $CVSAddress) ;
                $this->_orderService->setOrderData($orderId, 'ecpay_logistic_cvs_store_telephone', $CVSTelephone) ;

                // 更新訂單寄送資訊
                $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
                $dbWrite= $resource->getConnection();
                $dbWrite->update(
                    $resource->getTableName('sales_order_address'),
                    [
                        'region'    => NULL,
                        'postcode'  => $CVSStoreID,
                        'street'    => $CVSAddress . '(門市地址)',
                        'city'      => $CVSStoreName,
                        'company'   => NULL,
                    ],
                    [
                        'parent_id = ?'    => $orderId,
                        'address_type = ?' => 'shipping',
                    ]
                );

                $dbWrite->closeConnection();
            }
        }

        // 判斷是否為綠界金流
        $paymentMethod = $this->_orderService->getPaymentMethod($orderId);
        $this->_loggerInterface->debug('MapResponse paymentMethod:'. print_r($paymentMethod,true));

        if ($this->_paymentService->isEcpayPayment($paymentMethod)){

            // YES 轉導到綠界金流執行程序組合FORM(帶ORDER_ID走) -> PaymentToEcpay
            $redirectUrl = $this->_urlInterface->getUrl('ecpaygeneral/Process/PaymentToEcpay');
            $redirectUrl = $redirectUrl . '?id='. $enctyOrderId ;

        } else {

            // NO 轉到感謝頁面 帶ORDER_ID走
            $redirectUrl = $this->_urlInterface->getUrl('ecpaygeneral/Page/ThankYou');
            $redirectUrl = $redirectUrl . '?id='. $enctyOrderId ;
        }

        $this->_loggerInterface->debug('MapResponse $redirectUrl:'. print_r($redirectUrl,true));

        $this->_responseFactory->create()->setRedirect($redirectUrl)->sendResponse();

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