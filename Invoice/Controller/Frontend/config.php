<?php

namespace Ecpay\Invoice\Controller\Frontend;

use Ecpay\General\Helper\Services\Config\MainService;

class Config extends \Magento\Framework\App\Action\Action
{

    protected $_mainService;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        MainService $mainService
    )
    {
        $this->_mainService = $mainService;

        return parent::__construct($context);
    }

    public function execute()
    {
        echo 'INVOICE CONFIG' . '<br>-------------------------------<br>';
        echo '訂單編號前綴:' . $this->_mainService->getPaymentConfig('payment_order_prefix') . '<br>';
        echo '開立發票模式:' . $this->_mainService->getPaymentConfig('enabled_invoice_auto') . '<br>';
        echo '作廢發票模式:' . $this->_mainService->getPaymentConfig('enabled_cancel_invoice_auto') . '<br>';
        echo '延期開立天數:' . $this->_mainService->getPaymentConfig('invoice_dalay_date') . '<br>';
        echo '啟用測試模式:' . $this->_mainService->getPaymentConfig('enabled_invoice_stage') . '<br>';
        echo '商店代號:' . $this->_mainService->getPaymentConfig('invoice_mid') . '<br>';
        echo 'HashKey:' . $this->_mainService->getPaymentConfig('invoice_hashkey') . '<br>';
        echo 'HashIV:' . $this->_mainService->getPaymentConfig('invoice_hashiv') . '<br>';
        echo '-------------------------------<br>';
        exit();

    }
}