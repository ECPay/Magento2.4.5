<?php

namespace Ecpay\WebatmPaymentGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;
class PaymentMethod extends AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'ecpay_webatm_gateway';

    protected $_infoBlockType = 'Ecpay\\WebatmPaymentGateway\\Block\\Info';

}