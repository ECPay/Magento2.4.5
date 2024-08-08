<?php

namespace Ecpay\TwqrPaymentGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;
class PaymentMethod extends AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'ecpay_twqr_gateway';

    protected $_infoBlockType = 'Ecpay\\TwqrPaymentGateway\\Block\\Info';

}