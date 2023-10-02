<?php

namespace Ecpay\CreditPaymentGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;
class PaymentMethod extends AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'ecpay_credit_gateway';

    protected $_infoBlockType = 'Ecpay\\CreditPaymentGateway\\Block\\Info';

}