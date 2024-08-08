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
                template: 'Ecpay_TwqrPaymentGateway/payment/form'
            },

            getCode: function() {
                return 'ecpay_twqr_gateway';
            }
        });
    }
);
