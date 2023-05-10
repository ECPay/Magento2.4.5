<?php

namespace Ecpay\Invoice\Observer;

use Magento\Framework\DataObject\Copy;

class SaveToOrder implements \Magento\Framework\Event\ObserverInterface
{
    protected $_objectCopyService;

    public function __construct(
        Copy $objectCopyService
    ) {
        $this->_objectCopyService = $objectCopyService;
    }

    /**
     * List of attributes that should be added to an order.
     *
     * @var array
     */
    private $attributes = [
        'ecpay_invoice_carruer_type',
        'ecpay_invoice_type',
        'ecpay_invoice_carruer_num',
        'ecpay_invoice_love_code',
        'ecpay_invoice_customer_company',
        'ecpay_invoice_customer_identifier'
    ];

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $quote = $event->getData('quote');
        $order = $event->getData('order');

        foreach ($this->attributes as $attribute) {
            if ($quote->hasData($attribute)) {
                $order->setData($attribute, $quote->getData($attribute));
            }
        }

        //copy order to sale_order
        $this->_objectCopyService->copyFieldsetToTarget(
            'sales_convert_quote',
            'to_order',
            $quote,
            $order
        );

        return $this;
    }
}