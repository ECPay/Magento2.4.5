<?php

namespace Ecpay\CreditInstallmentPaymentGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;
class PaymentMethod extends AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'ecpay_credit_installment_gateway';

    protected $_infoBlockType = 'Ecpay\\CreditInstallmentPaymentGateway\\Block\\Info';

}