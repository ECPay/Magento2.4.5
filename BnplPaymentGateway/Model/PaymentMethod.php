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
}