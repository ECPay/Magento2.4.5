var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/place-order': {
                'Ecpay_Invoice/js/order/place-order-mixin': true
            },
            'Magento_Checkout/js/action/set-shipping-information': {
                'Ecpay_Invoice/js/action/set-shipping-information-mixin': true
            }
        }
    }
};