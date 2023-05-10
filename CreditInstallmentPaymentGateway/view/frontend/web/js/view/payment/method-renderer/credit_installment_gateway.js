/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Ecpay_CreditInstallmentPaymentGateway/payment/form',
                ecpayCreditInstallment: ''
            },

            initObservable: function () {

                this._super()
                    .observe([
                        'ecpayCreditInstallment'
                    ]);
                return this;
            },

            getCode: function() {
                return 'ecpay_credit_installment_gateway';
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'ecpay_credit_installment': this.ecpayCreditInstallment()
                    }
                };
            },

            getecpayCreditInstallments: function() {
                // Ecpay\CreditInstallmentPaymentGateway\Model\Ui\ConfigProvider.php
                return _.map(window.checkoutConfig.payment.ecpay_credit_installment_gateway.ecpayCreditInstallments, function(value, key) {
                    return {
                        'value': key,
                        'ecpay_credit_installment': value
                    }
                });
            }
        });
    }
);
