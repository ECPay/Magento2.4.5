<?php

namespace Ecpay\AtmPaymentGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;
class PaymentMethod extends AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'ecpay_atm_gateway';

    protected $_infoBlockType = 'Ecpay\\AtmPaymentGateway\\Block\\Info';

}