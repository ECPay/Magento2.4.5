define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (setShippingInformationAction) {
        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            var ecpay_invoice_type = $("#ecpay_invoice_type").val();

            switch (ecpay_invoice_type) {
                case 'd':
                    var ecpay_invoice_love_code = $("#ecpay_invoice_love_code").val();
                    if (ecpay_invoice_love_code == '' || ecpay_invoice_love_code == null) {
                        $("#ecpay_invoice_love_code").css({'border-color': '#ed8380'});
                        $("#ecpay_invoice_love_code").focus();
                        $("#love_code_error").empty();
                        $("#love_code_error").append('<span>This is a required field.</span>');
                        return false;
                    }
                    break;
                case 'c':
                    var ecpay_invoice_customer_identifier = $("#ecpay_invoice_customer_identifier").val();
                    var ecpay_invoice_company = $("#ecpay_invoice_customer_company").val();
                    if (ecpay_invoice_customer_identifier == '' || ecpay_invoice_customer_identifier == null || ecpay_invoice_company == '' || ecpay_invoice_customer_identifier == null) {
                        if (ecpay_invoice_customer_identifier == '' || ecpay_invoice_customer_identifier == null) {
                            $("#ecpay_invoice_customer_identifier").css({'border-color': '#ed8380'});
                            $("#ecpay_invoice_customer_identifier").focus();
                            $("#customer_identifier_error").empty();
                            $("#customer_identifier_error").append('<span>This is a required field.</span>');
                        }
                        if (ecpay_invoice_company == '' || ecpay_invoice_company == null) {
                            $("#ecpay_invoice_customer_company").css({'border-color': '#ed8380'});
                            $("#ecpay_invoice_customer_company").focus();
                            $("#customer_company_error").empty();
                            $("#customer_company_error").append('<span>This is a required field.</span>');
                        }
                        return false;
                    }
                    break;
                default:
                    var ecpay_invoice_carruer_type = $("#ecpay_invoice_carruer_type").val();
                    if (ecpay_invoice_carruer_type == '2' || ecpay_invoice_carruer_type == '3') {
                        var ecpay_invoice_carruer_num = $("#ecpay_invoice_carruer_num").val();
                        if (ecpay_invoice_carruer_num == '' || ecpay_invoice_carruer_num == null) {
                            $("#ecpay_invoice_carruer_num").css({'border-color': '#ed8380'});
                            $("#ecpay_invoice_carruer_num").focus();
                            $("#carruer_num_error").empty();
                            $("#carruer_num_error").append('<span>This is a required field.</span>');
                            return false;
                        }
                    }
                    break;
            }

            var result = originalAction();
            return result;
        });
    };
});
             