<?php

namespace Ecpay\CvsPaymentGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;
class PaymentMethod extends AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'ecpay_cvs_gateway';

    protected $_infoBlockType = 'Ecpay\\CvsPaymentGateway\\Block\\Info';

}