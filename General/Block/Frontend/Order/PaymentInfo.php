<?php

namespace Ecpay\General\Block\Frontend\Order;

use Magento\Sales\Block\Order\Info;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\PaymentService;

class PaymentInfo extends Info
{
    protected $_orderService;
    protected $_paymentService;

    protected $orderId;

    /**
     * @param TemplateContext $context
     * @param Registry $registry
     * @param PaymentHelper $paymentHelper
     * @param AddressRenderer $addressRenderer
     * @param OrderService $orderService
     * @param PaymentService $paymentService
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        PaymentHelper $paymentHelper,
        AddressRenderer $addressRenderer,
        OrderService $orderService,
        PaymentService $paymentService,
        array $data = []
    ) {
        parent::__construct($context, $registry, $paymentHelper, $addressRenderer, $data);

        $this->_orderService = $orderService;
        $this->_paymentService = $paymentService;

        $this->orderId = $this->getOrder()->getId();
        $this->_logger->debug('Frontend PaymentInfo Block $this->orderId : ' . $this->getOrder()->getIdFieldName() . ' ' . $this->orderId);
    }

    /**
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return parent::getPaymentInfoHtml() . $this->getEcpayPaymentInfoHtml();
    }

    /**
     * @return string
     */
    public function getEcpayPaymentInfoHtml()
    {
        $html  = '<dl class="ecpay-payment-method">';
        foreach ($this->getEcpayPaymentInfo() as $info) {
            $html .= '    <dt class="title">' . $info['key'] . '：' . $info['val'] . '</dt>';
        }
        $html .= '</dl>';

        return $html;
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