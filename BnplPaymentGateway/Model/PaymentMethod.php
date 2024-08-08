<?php

namespace Ecpay\BnplPaymentGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\App\ObjectManager;

class PaymentMethod extends AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'ecpay_bnpl_gateway';

    protected $_infoBlockType = 'Ecpay\\BnplPaymentGateway\\Block\\Info';

    public function isAvailable(CartInterface $quote = null)
    {
        // 訂單金額達大於2999開放無卡分期
        $objectManager = ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $grandTotal  = $cart->getQuote()->getGrandTotal();

        if ($grandTotal > 2999) {
            return parent::isAvailable($quote);
        }

        return false;
    }

}