<?php

namespace Ecpay\ApplepayPaymentGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;

class PaymentMethod extends AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'ecpay_applepay_gateway';

    protected $_infoBlockType = 'Ecpay\\ApplepayPaymentGateway\\Block\\Info';

    public function isAvailable(CartInterface $quote = null)
    {
        return $this->isApplePaySupported();
    }

    /**
     * 檢查使用者裝置是否支援 Apple Pay
     */
    private function isApplePaySupported()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('/iPhone|iPad|iPod/', $userAgent) === 1) {
            return true;
        }

        return false;
    }
}