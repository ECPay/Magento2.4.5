<?php
namespace Ecpay\General\Controller\Process;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface ;

use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Foundation\EncryptionsHelper;
use Ecpay\General\Helper\Foundation\GeneralHelper;

class PaymentResponse extends Action implements CsrfAwareActionInterface
{

    protected $_loggerInterface;
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
        Context $context,
        RequestInterface $requestInterface,

        EncryptionsService $encryptionsService,
        OrderService $orderService,

        MainService $mainService,
        InvoiceService $invoiceService,
        LogisticService $logisticService,
        PaymentService $paymentService,

        EncryptionsHelper $encryptionsHelper,
        GeneralHelper $generalHelper
    )
    {
        $this->_loggerInterface = $loggerInterface;
        $this->_requestInterface = $requestInterface;

        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;

        $this->_mainService = $mainService;
        $this->_invoiceService = $invoiceService;
        $this->_logisticService = $logisticService;
        $this->_paymentService = $paymentService;

        $this->_encryptionsHelper = $encryptionsHelper;
        $this->_generalHelper = $generalHelper;

        return parent::__construct($context);
    }

    public function execute()
    {
        // 接收金流資訊
        $paymentInfo = $this->_requestInterface->getPostValue();
        $this->_loggerInterface->debug('PaymentResponse paymentInfo:'. print_r($paymentInfo,true));

        if (count($paymentInfo) < 1) {
            throw new Exception('Get ECPay feedback failed.');
        } else {

            // 取得原始訂單編號
            $orderInfo = $this->_orderService->getOrderIdByPaymentMerchantTradeNo($paymentInfo['MerchantTradeNo']);
            $this->_loggerInterface->debug('PaymentResponse orderInfo:'. print_r($orderInfo,true));

            if (isset($orderInfo['entity_id']) && $orderInfo['entity_id'] !== '') {
                $orderId = intval($orderInfo['entity_id']);
            } else {
                $enctyOrderId = $this->getRequest()->getParam('id') ;
                $enctyOrderId = str_replace(' ', '+', $enctyOrderId) ;
                $orderId      = intval($this->_encryptionsService->decrypt($enctyOrderId));
            }
            $this->_loggerInterface->debug('PaymentResponse orderId:'. print_r($orderId, true));

            // 取出 KEY IV MID
            $accountInfo = $this->_paymentService->getStageAccount();
            if (!$paymentInfo['MerchantID'] == $accountInfo['MerchantId']) {
                $accountInfo = [
                    'HashKey' => $this->_mainService->getPaymentConfig('payment_hashkey'),
                    'HashIv'  => $this->_mainService->getPaymentConfig('payment_hashiv'),
                ] ;
            }
            $this->_loggerInterface->debug('PaymentToEcpay accountInfo:'. print_r($accountInfo,true));

            // 驗證參數(金額及checkMacValue)
            $checkMacValue = $this->_paymentService->checkMacValue($accountInfo, $paymentInfo);
            $orderCurrencyCode = $this->_orderService->getOrderCurrencyCode($orderId);
            $orderTotal = (!$orderCurrencyCode) ? $this->_orderService->getGrandTotal($orderId) : $this->_orderService->getBaseGrandTotal($orderId);
            if (!$this->_paymentService->validAmount($paymentInfo['TradeAmt'], $orderTotal) || !$checkMacValue) {
                $this->_loggerInterface->debug('PaymentResponse $paymentInfo.TradeAmt:'. print_r($paymentInfo['TradeAmt'], true));
                $this->_loggerInterface->debug('PaymentResponse $orderTotal:'. print_r($orderTotal, true));
                throw new Exception('Order amount are not identical.');
            } else {

                // 訂單狀態判斷
                $createStatus = [Order::STATE_PENDING_PAYMENT, 'ecpay_pending_payment'];
                $orderStatus = $this->_orderService->getStatus($orderId);
                $this->_loggerInterface->debug('PaymentResponse $orderStatus:'. print_r($orderStatus, true));

                // 付款完成標籤 0.未付款完成 1.付款完成
                $paymentCompleteFlag = $this->_orderService->getEcpayPaymentCompleteTag($orderId);

                // 訂單處理
                if (in_array($orderStatus, $createStatus) && !$paymentCompleteFlag) {

                    // 判斷是否為模擬付款
                    if (isset($paymentInfo['SimulatePaid']) && intval($paymentInfo['SimulatePaid']) === 0){

                        $responseResult = $this->_paymentService->getReturnUrlResponse($paymentInfo);
                        $this->_loggerInterface->debug('PaymentResponse $responseResult:'. print_r($responseResult, true));

                        // 更新訂單狀態
                        $this->_orderService->setOrderState($orderId, $responseResult['status']);
                        $this->_orderService->setOrderStatus($orderId, $responseResult['status']);

                        // 更新訂單備註
                        $this->_orderService->setOrderCommentForBack($orderId, $responseResult['comment']);

                        // 異動旗標
                        $this->_orderService->setOrderData($orderId, 'ecpay_payment_complete_tag', 1) ;
                    } else {
                        // 模擬付款，僅更新備註
                        $this->_orderService->setOrderCommentForBack($orderId, __('Simulate paid, update the note only.'));
                    }
                }
            }
        }

        echo '1|OK' ;

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