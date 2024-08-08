define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
], function ($, wrapper, quote) {
    'use strict';

    return function (setShippingInformationAction) {
        return wrapper.wrap(setShippingInformationAction, function (originalAction) {

            // 重置欄位style及error msg
            $("#ecpay_invoice_love_code").removeAttr("style");
            $("#ecpay_invoice_customer_identifier").removeAttr("style");
            $("#ecpay_invoice_customer_company").removeAttr("style");
            $("#ecpay_invoice_carruer_num").removeAttr("style");
            $("#love_code_error").empty();
            $("#customer_identifier_error").empty();
            $("#customer_company_error").empty();
            $("#carruer_num_error").empty();

            var error = false;
            
            // 依照發票開立類型判斷需驗證欄位(個人p、公司c、捐贈d)
            var ecpay_invoice_type = $("#ecpay_invoice_type").val();
            switch (ecpay_invoice_type) {
                case 'd':
                    var ecpay_invoice_love_code = $("#ecpay_invoice_love_code").val();
                    // 未填寫捐贈碼
                    if (ecpay_invoice_love_code == '' || ecpay_invoice_love_code == null) {
                        addErrorMsg('love_code', 'This is a required field.')
                    }
                    else {
                        // 驗證捐贈碼
                        var data = {
                            'loveCode': ecpay_invoice_love_code
                        }
                        verifyAjax('love_code', 'rest/V1/ecpay_general/invoice/check_love_code', data);
                    }
                    break;
                case 'c':
                    var ecpay_invoice_customer_identifier = $("#ecpay_invoice_customer_identifier").val();
                    var ecpay_invoice_carruer_type = $("#ecpay_invoice_carruer_type").val();
                    var ecpay_invoice_company = $("#ecpay_invoice_customer_company").val();

                    // 未填寫公司統編
                    if (ecpay_invoice_customer_identifier == '' || ecpay_invoice_customer_identifier == null) {
                        addErrorMsg('customer_identifier', 'This is a required field.')
                    }
                    else {
                        var data = {
                            'businessNumber': ecpay_invoice_customer_identifier
                        }
                        verifyAjax('customer_identifier', 'rest/V1/ecpay_general/invoice/check_business_number', data);
                    }

                    // 紙本發票未填寫公司行號
                    if (ecpay_invoice_carruer_type == '0' && (ecpay_invoice_company == '' || ecpay_invoice_company == null)) {
                        addErrorMsg('customer_company', 'This is a required field.')
                    }

                    // 載具類型選擇自然人憑證或手機條碼，未填寫載具編號
                    if (ecpay_invoice_carruer_type == '2' || ecpay_invoice_carruer_type == '3') {
                        var ecpay_invoice_carruer_num = $("#ecpay_invoice_carruer_num").val();
                        if (ecpay_invoice_carruer_num == '' || ecpay_invoice_carruer_num == null) {
                            addErrorMsg('carruer_num', 'This is a required field.')
                        }
                        else {
                            // 驗證載具自然人憑證(2)、手機條碼(3)
                            if (ecpay_invoice_carruer_type == '2') {
                                var path_url = 'rest/V1/ecpay_general/invoice/check_citizen_digital_certificate';
                                var data = {
                                    'carrierNumber': ecpay_invoice_carruer_num
                                }
                            }
                            else {
                                var path_url = 'rest/V1/ecpay_general/invoice/check_barcode';
                                var data = {
                                    'barcode': ecpay_invoice_carruer_num
                                }
                            }
                            verifyAjax('carruer_num', path_url, data);
                        }
                    }

                    break;
                default:
                    // 個人發票
                    var ecpay_invoice_carruer_type = $("#ecpay_invoice_carruer_type").val();
                    if (ecpay_invoice_carruer_type == '2' || ecpay_invoice_carruer_type == '3') {
                        // 個人發票選擇載具選項
                        var ecpay_invoice_carruer_num = $("#ecpay_invoice_carruer_num").val();
                        if (ecpay_invoice_carruer_num == '' || ecpay_invoice_carruer_num == null) {
                            addErrorMsg('carruer_num', 'This is a required field.')
                        }
                        else {
                            // 驗證載具自然人憑證(2)、手機條碼(3)
                            if (ecpay_invoice_carruer_type == '2') {
                                var path_url = 'rest/V1/ecpay_general/invoice/check_citizen_digital_certificate';
                                var data = {
                                    'carrierNumber': ecpay_invoice_carruer_num
                                }
                            }
                            else {
                                var path_url = 'rest/V1/ecpay_general/invoice/check_barcode';
                                var data = {
                                    'barcode': ecpay_invoice_carruer_num
                                }
                            }
                            verifyAjax('carruer_num', path_url, data);
                        }
                    }
                    break;
            }

            // 驗證欄位Ajax
            function verifyAjax(field, url_path, data) {
                $.ajax({ 
                    type: 'POST',
                    url: window.BASE_URL + url_path,
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8',
                        'dataType': 'json',
                    },
                    async: false,
                    data: JSON.stringify(data),
                    error: function(response) {
                        var errorMsg = response.responseJSON;
                        addErrorMsg(field, errorMsg.message + ' (' + errorMsg.code + ')')
                    },
                    success: function(response) {
                        if (response.code != '0999') {
                            addErrorMsg(field, response.msg + ' (' + response.code + ')')
                        }
                    }
                });
            }

            // 顯示特定欄位錯誤訊息
            function addErrorMsg(field, msg) {
                $("#ecpay_invoice_" + field).css({'border-color': '#ed8380'});
                $("#ecpay_invoice_" + field).focus();
                $("#" + field + "_error").empty();
                $("#" + field + "_error").append('<span>' + msg + '</span>');
                error = true;
            }

            if (error) return false;

            var result = originalAction();
            return result;
        });
    };
});
             