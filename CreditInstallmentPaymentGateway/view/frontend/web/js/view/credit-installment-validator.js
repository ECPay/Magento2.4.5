define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Ecpay_CreditInstallmentPaymentGateway/js/model/credit-installment-validator'
    ],
    function (Component, additionalValidators, creditInstallmentValidator) {
        'use strict';
        additionalValidators.registerValidator(creditInstallmentValidator);
        return Component.extend({});
    }
);