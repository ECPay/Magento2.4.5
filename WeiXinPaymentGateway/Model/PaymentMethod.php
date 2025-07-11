<?php

namespace Ecpay\WeiXinPaymentGateway\Model;

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
    protected $_code = 'ecpay_weixin_gateway';

    protected $_infoBlockType = 'Ecpay\\WeiXinPaymentGateway\\Block\\Info';

    public function isAvailable(CartInterface $quote = null)
    {
        $objectManager = ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $grandTotal  = $cart->getQuote()->getGrandTotal();

        // 訂單金額不可小於6元或大於50萬元
        if (6 > $grandTotal || $grandTotal > 500000) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}