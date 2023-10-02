<?php

namespace Ecpay\ApplepayPaymentGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;
class PaymentMethod extends AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'ecpay_applepay_gateway';

    protected $_infoBlockType = 'Ecpay\\ApplepayPaymentGateway\\Block\\Info';
}