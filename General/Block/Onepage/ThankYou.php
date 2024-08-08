<?php
namespace Ecpay\General\Block\Onepage;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Psr\Log\LoggerInterface;

use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Services\Config\LogisticService;

class ThankYou extends Template
{
    protected $_checkoutSession;

    protected $_loggerInterface;

    protected $_encryptionsService;
    protected $_orderService;

    protected $_paymentService;
    protected $_logisticService;

    protected $orderId;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        LoggerInterface $loggerInterface,
        EncryptionsService $encryptionsService,
        OrderService $orderService,
        PaymentService $paymentService,
        LogisticService $logisticService,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_loggerInterface = $loggerInterface;

        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;

        $this->_paymentService = $paymentService;
        $this->_logisticService = $logisticService;

        parent::__construct($context, $data);

        $this->orderId = $this->getOrderId();
        $this->_loggerInterface->debug('ThankYou Block orderId:'. print_r($this->orderId,true));
    }

    /**
     * 檢查 Order ID，是否顯示
     *
     * @return bool
     */
    public function isShow()
    {
        return ($this->orderId !== 0);
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->_orderService->getOrder($this->orderId);
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->getOrder()->getPayment();
    }

    /**
     * 取得訂單資訊
     *
     * @return array
     */
    public function getOrderInfo()
    {
        return [
            'real_order_id' => $this->_orderService->getRealOrderId($this->orderId),
            'created_at'    => $this->_orderService->getCreatedAt($this->orderId),
            'amount'        => $this->_orderService->getBaseGrandTotal($this->orderId),
            'state'         => $this->_orderService->getOrderState($this->orderId),
        ];
    }

    /**
     * 取得付款資訊
     *
     * @return array
     */
    public function getPaymentInfo()
    {
        $paymentMethod = $this->_orderService->getPaymentMethod($this->orderId);
        $methodTitle = $this->getPayment()->getMethodInstance()->getTitle();
        $this->_loggerInterface->debug('ThankYou Block paymentMethod:'. print_r($paymentMethod,true));
        $this->_loggerInterface->debug('ThankYou Block methodTitle:'. print_r($methodTitle,true));

        $isEcpayPayment = $this->_paymentService->isEcpayPayment($paymentMethod);
        $this->_loggerInterface->debug('ThankYou Block isEcpayPayment:'. print_r($isEcpayPayment,true));

        // 判斷是否為綠界金流
        $paymentInfo = [];
        if ($isEcpayPayment) {
            $paymentInfo = $this->_orderService->getEcpayPaymentInfoContent($this->orderId, $paymentMethod);
        }
        $this->_loggerInterface->debug('ThankYou Block paymentInfo:'. print_r($paymentInfo,true));

        return [
            'is_ecpay_payment' => ($isEcpayPayment) ? 'Y' : 'N',
            'payment_method'   => $methodTitle,
            'payment_info'     => $paymentInfo,
        ];
    }

    /**
     * 取得運送資訊
     *
     * @return array
     */
    public function getShippingInfo()
    {
        $shippingMethod = $this->_orderService->getShippingMethod($this->orderId);
        $shippingMethod = empty($shippingMethod) ? '' : $shippingMethod;
        $methodTitle = $this->_orderService->getShippingDescription($this->orderId);
        $this->_loggerInterface->debug('ThankYou Block shippingMethod:'. print_r($shippingMethod,true));
        $this->_loggerInterface->debug('ThankYou Block methodTitle:'. print_r($methodTitle,true));

        $isEcpayCvsLogistics = $this->_logisticService->isEcpayCvsLogistics($shippingMethod);
        $this->_loggerInterface->debug('ThankYou Block isEcpayCvsLogistics:'. print_r($isEcpayCvsLogistics,true));

        // 判斷是否為綠界超商物流
        $cvsInfo = [];
        if ($isEcpayCvsLogistics) {
            $cvsInfo = [
                'cvs_store_id'      => $this->_orderService->getEcpayLogisticCvsStoreId($this->orderId),
                'cvs_store_name'    => $this->_orderService->getEcpayLogisticCvsStoreName($this->orderId),
                'cvs_store_address' => $this->_orderService->getEcpayLogisticCvsStoreAddress($this->orderId)
            ];
        }

        return [
            'is_ecpay_cvs_logistics' => ($isEcpayCvsLogistics) ? 'Y' : 'N',
            'shipping_method'        => $methodTitle,
            'cvs_info'               => $cvsInfo,
        ];
    }

    /**
     * @return string
     */
    public function getContinueUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * 取得訂單編號
     *
     * @return int
     */
    private function getOrderId()
    {
        // 解密訂單編號
        $enctyOrderId = $this->getRequest()->getParam('id') ;
        $enctyOrderId = str_replace(' ', '+', $enctyOrderId) ;
        $this->_loggerInterface->debug('ThankYou Block enctyOrderId:'. print_r($enctyOrderId,true));

        $orderId      = $this->_encryptionsService->decrypt($enctyOrderId);
        $this->_loggerInterface->debug('ThankYou Block orderId:'. print_r(intval($orderId),true));

        return intval($orderId);
    }

    /**
     *  Payment custom error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        $error = $this->_checkoutSession->getErrorMessage();
        return $error;
    }
}