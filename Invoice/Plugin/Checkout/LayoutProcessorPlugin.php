<?php
namespace Ecpay\Invoice\Plugin\Checkout;

use Psr\Log\LoggerInterface;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Common\OrderService;

class LayoutProcessorPlugin
{
    protected $_loggerInterface;
    protected $_mainService;
    protected $_orderService;

    public function __construct(
        LoggerInterface $loggerInterface,
        MainService $mainService, 
        OrderService $orderService
    ){
        $this->_loggerInterface = $loggerInterface;
        $this->_mainService = $mainService;
        $this->_orderService = $orderService;
    }
    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array  $jsLayout
    ) {
        $ecpayEnableIvoice = $this->_mainService->getMainConfig('ecpay_enabled_invoice');
        $this->_loggerInterface->debug('checkout page invoice status:'. print_r($ecpayEnableIvoice, true));

        $attributeCode = 'custom_form';
        $fieldConfiguration = [
            'component' => 'Ecpay_Invoice/js/custom-attributes-form',
            'config' => [
                'customScope' => 'shippingAddress.extension_attributes',
                'template' => 'Ecpay_Invoice/custom-attributes-form',
                'id' => 'custom_form'
            ],
            'dataScope' => 'shippingAddress.extension_attributes' . '.' . $attributeCode,
            'label' => __('Custom Form'),
            'provider' => 'checkoutProvider',
            'visible' => true,
            'validation' => [],
            'sortOrder' => 200,
            'validation' => [
                'required-entry' => true
            ],
            'id' => 'custom_form',
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'value' => ''
        ];

        if ($ecpayEnableIvoice != 1) {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                ['shippingAddress']['children']['before-form']['children'][$attributeCode]['visible'] = false;
        }
        else {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                ['shippingAddress']['children']['before-form']['children'][$attributeCode] = $fieldConfiguration;
        }

        return $jsLayout;
    }
}