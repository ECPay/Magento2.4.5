<?php
namespace Ecpay\Invoice\Plugin\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Psr\Log\LoggerInterface;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Common\OrderService;

class CompositeConfigProvider
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

    public function afterGetConfig(
        ConfigProviderInterface $subject,
        $result
    ) {
        $love_code = $this->_mainService->getInvoiceConfig('invoice_donate');
        $result['default_love_code'] = $love_code;

        return $result;
    }

}