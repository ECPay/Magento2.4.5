<?php

namespace Ecpay\General\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View\Tab\Info;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\PaymentService;

class PaymentInfo extends Info
{
    protected $_orderService;
    protected $_paymentService;
    protected $_orderRepository;

    protected $orderId;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        OrderService $orderService,
        PaymentService $paymentService,
        array $data = [],
        ?ShippingHelper $shippingHelper = null,
        ?TaxHelper $taxHelper = null
    ) {
        parent::__construct($context, $registry, $adminHelper, $data, $shippingHelper, $taxHelper);

        $this->_orderService = $orderService;
        $this->_paymentService = $paymentService;
        $this->_orderRepository = $orderRepository;

        $this->orderId = $this->getOrder()->getId();
        $this->_logger->debug('Admin PaymentInfo Block $this->orderId : ' . $this->getOrder()->getIdFieldName() . ' ' . $this->orderId);
    }

    /**
     * 是否顯示綠界付款資訊
     *
     * @return bool
     */
    public function isShowEcpayPaymentInfo()
    {
        $allowedPaymentInfoList = ['ecpay_atm_gateway', 'ecpay_cvs_gateway', 'ecpay_barcode_gateway', 'ecpay_credit_installment_gateway'];
        $paymentMethod = $this->_orderService->getPaymentMethod($this->orderId);

        if (in_array($paymentMethod, $allowedPaymentInfoList)) {
            return true;
        }

        return false;
    }

    /**
     * 取得綠界付款資訊
     *
     * @return array $paymentInfo
     */
    public function getEcpayPaymentInfo()
    {
        $paymentInfo = [];

        $paymentMethod = $this->_orderService->getPaymentMethod($this->orderId);

        if ($this->_paymentService->isEcpayPayment($paymentMethod)) {
            $paymentInfo = $this->_orderService->getEcpayPaymentInfoContent($this->orderId, $paymentMethod);
        }

        return $paymentInfo;
    }
}