<?php

namespace Ecpay\BarcodePaymentGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;
class PaymentMethod extends AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'ecpay_barcode_gateway';

    protected $_infoBlockType = 'Ecpay\\BarcodePaymentGateway\\Block\\Info';

}