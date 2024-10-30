<?php

namespace Ecpay\Invoice\Controller\Frontend;

use Ecpay\General\Helper\Services\Config\MainService;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseFactory;

class Config extends Action
{
    protected $_mainService;
    protected $_responseFactory;

    public function __construct(
        Context $context,
        MainService $mainService,
        ResponseFactory $responseFactory
    )
    {
        $this->_mainService = $mainService;
        $this->_responseFactory = $responseFactory;

        return parent::__construct($context);
    }

    public function execute()
    {
        $response = $this->_responseFactory->create();
        $response->setHttpResponseCode(200); // 設置 HTTP 狀態碼

        $body = 'INVOICE CONFIG' . '<br>-------------------------------<br>訂單編號前綴:' . $this->_mainService->getPaymentConfig('payment_order_prefix') . '<br>開立發票模式:' . $this->_mainService->getPaymentConfig('enabled_invoice_auto') . '<br>作廢發票模式:' . $this->_mainService->getPaymentConfig('enabled_cancel_invoice_auto') . '<br>延期開立天數:' . $this->_mainService->getPaymentConfig('invoice_dalay_date') . '<br>啟用測試模式:' . $this->_mainService->getPaymentConfig('enabled_invoice_stage') . '<br>商店代號:' . $this->_mainService->getPaymentConfig('invoice_mid') . '<br>HashKey:' . $this->_mainService->getPaymentConfig('invoice_hashkey') . '<br>HashIV:' . $this->_mainService->getPaymentConfig('invoice_hashiv') . '<br>-------------------------------<br>';
        $response->setBody($body); // 設置回應的內容
        return $response->sendResponse();
    }
}