<?php
namespace Ecpay\General\Block\Onepage;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Psr\Log\LoggerInterface;

use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Common\ToEcpayService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Services\Config\LogisticService;

class RedirectToEcpay extends Template
{
    protected $_checkoutSession;

    protected $_loggerInterface;

    protected $_encryptionsService;
    protected $_orderService;
    protected $_toEcpayService;

    protected $_paymentService;
    protected $_logisticService;

    protected $orderId;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        LoggerInterface $loggerInterface,
        EncryptionsService $encryptionsService,
        OrderService $orderService,
        ToEcpayService $toEcpayService,
        PaymentService $paymentService,
        LogisticService $logisticService,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_loggerInterface = $loggerInterface;

        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_toEcpayService = $toEcpayService;

        $this->_paymentService = $paymentService;
        $this->_logisticService = $logisticService;

        parent::__construct($context, $data);
    }

    /**
     * 產生綠界金、物流表單後至前端 echo
     */
    public function getFormHtml() {
        $enctyOrderId = $this->getRequest()->getParam('id');
        $formType = $this->getRequest()->getParam('type');

        if ($formType == 'payment') $form = $this->_toEcpayService->preparePayment($enctyOrderId);
        else $form = $this->_toEcpayService->prepareLogistic($enctyOrderId);

        return $form;
    }
}