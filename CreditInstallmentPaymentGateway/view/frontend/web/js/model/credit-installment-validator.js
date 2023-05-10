define(
    [
        'jquery',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/quote'
    ],
    function ($, $t, messageList, quote) {
        'use strict';
        return {
            validate: function () {

                let isValid = true;
                const paymentMethod = quote.paymentMethod().method;
                const dataDiv = $('#ecpay_payment');
                const choosenCreditInstallment = dataDiv.find('select[name="ecpay_credit_installments"]').val();

                var ecpayCreditInstallments = window.checkoutConfig.payment.ecpay_credit_installment_gateway.ecpayCreditInstallments;
                ecpayCreditInstallments =  _.map(ecpayCreditInstallments, function(value, key) {
                    return key;
                });

                const found = ecpayCreditInstallments.find(element => element === choosenCreditInstallment);

                if (paymentMethod === 'ecpay_credit_installment_gateway') {
                    if (found === undefined) {
                        isValid = false;
                    }
                }

                if (!isValid) {
                    messageList.addErrorMessage({ message: $t('Invalid number of periods.') });
                }

                return isValid;
            }
        }
    }
);