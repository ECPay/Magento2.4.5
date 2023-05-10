<?php

namespace Ecpay\Invoice\Controller\Frontend;

use \Magento\Framework\App\Action\Context;
use \Magento\Quote\Model\QuoteIdMaskFactory;
use \Magento\Quote\Api\CartRepositoryInterface;
use \Magento\Framework\Exception\NoSuchEntityException;

class Save extends \Magento\Framework\App\Action\Action
{
    protected $_quoteIdMaskFactory;
    protected $_quoteRepository;

    public function __construct(
        Context $context,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($context);
        $this->_quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->_quoteRepository = $quoteRepository;
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

    /**
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        if ($post) {

            $cartId = $post['cartId'];
            $loggin = $post['is_customer'];

            if ($loggin === 'false') {
                $cartId = $this->_quoteIdMaskFactory->create()->load($cartId, 'masked_id')->getQuoteId();
            }

            $quote = $this->_quoteRepository->getActive($cartId);
            if (!$quote->getItemsCount()) {
                throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
            }

            foreach ($this->attributes as $attribute) {
                if ($quote->hasData($attribute) && isset($post[$attribute])) {
                    $quote->setData($attribute, $post[$attribute]);
                }
            }

            if($quote->getData('ecpay_invoice_type') == '公司' || $quote->getData('ecpay_invoice_type') == '捐贈') {
                $quote->setData('ecpay_invoice_carruer_type', '');
            }

            $this->_quoteRepository->save($quote);
        }
    }
}