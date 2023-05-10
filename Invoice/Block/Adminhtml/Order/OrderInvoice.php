<?php

namespace Ecpay\Invoice\Block\Adminhtml\Order;

use Ecpay\General\Model\EcpayInvoice;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Helper\Admin;
use Magento\Sales\Block\Adminhtml\Order\AbstractOrder;

class OrderInvoice extends AbstractOrder
{
    protected $_encryptionsService;
    protected $_orderService;
    protected $_mainService;
    protected $_invoiceService;

    protected $orderId;

    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        EncryptionsService $encryptionsService,
        OrderService $orderService,
        MainService $mainService,
        InvoiceService $invoiceService,
        array $data = []
    ){
        parent::__construct($context, $registry, $adminHelper, $data);

        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_invoiceService = $invoiceService;

        $this->orderId = (int) $this->getOrder()->getId();
    }

    public function getTitle()
    {
        return 'Invoice Method';
    }

    /**
     * 發票模組是否啟動
     *
     * @return bool
     */
    public function getIvoiceModuleEnable()
    {
        return $this->_mainService->getMainConfig('ecpay_enabled_invoice');
    }

    /**
     * 取得加密資料
     *
     * @return string $encryptData
     */
    public function getEncryptData()
    {
        $encryptData = [
            'order_id'     => $this->_encryptionsService->encrypt($this->orderId),
            'protect_code' => $this->_orderService->getProtectCode($this->orderId),
        ];

        return json_encode($encryptData);
    }

    /**
     * 取得發票資料
     *
     * @return string
     */
    public function getInvoiceData()
    {
        // 取得發票資料
        $invoiceTypeCode = $this->_orderService->getecpayInvoiceType($this->orderId);
        $invoiceData = [
            'ecpay_invoice_tag'           => $this->_orderService->getEcpayInvoiceTag($this->orderId),
            'ecpay_invoice_type'          => $this->_invoiceService->getInvoiceTypeTable()[$invoiceTypeCode],
            'ecpay_invoice_date'          => $this->_orderService->getEcpayInvoiceDate($this->orderId),
            'ecpay_invoice_issue_type'    => $this->_orderService->getEcpayInvoiceIssueType($this->orderId),
            'ecpay_invoice_number'        => $this->_orderService->getEcpayInvoiceNumber($this->orderId),
            'ecpay_invoice_od_sob'        => $this->_orderService->getEcpayInvoiceOdSob($this->orderId),
            'ecpay_invoice_random_number' => $this->_orderService->getEcpayInvoiceRandomNumber($this->orderId),
        ];

        // 依照 ecpayInvoiceType 加入資料
        $extension = [];
        switch ($invoiceTypeCode) {
            case EcpayInvoice::ECPAY_INVOICE_TYPE_P:
                $extension = [
                    'ecpay_invoice_carruer_num' => $this->_orderService->getEcpayInvoiceCarruerNum($this->orderId),
                ];
                break;
            case EcpayInvoice::ECPAY_INVOICE_TYPE_C:
                $extension = [
                    'ecpay_invoice_customer_company' => $this->_orderService->getEcpayInvoiceCustomerCompany($this->orderId),
                    'ecpay_invoice_customer_identifier' => $this->_orderService->getEcpayInvoiceCustomerIdentifier($this->orderId),
                ];
                break;
            case EcpayInvoice::ECPAY_INVOICE_TYPE_D:
                $extension = [
                    'ecpay_invoice_love_code' => $this->_orderService->getEcpayInvoiceLoveCode($this->orderId),
                ];
                break;
        }
        $invoiceData = array_merge($invoiceData, $extension);

        return json_encode($invoiceData);
    }
}
