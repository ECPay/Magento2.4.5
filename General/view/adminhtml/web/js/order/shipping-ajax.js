define([
    'jquery',
    'mage/translate'
], function($, $t) {
    'use strict';

    return function(config, element) {
        var shippingFunction = {
            shippingMethod: '',
            getShippingMethod: function() {
                this.shippingAjax('rest/V1/ecpay_general/logistic/get_shipping_mothod', 'GET', 'shipment');
            },
            getShippingTag: function() {
                this.shippingAjax('rest/V1/ecpay_general/logistic/get_shipping_tag', 'GET', 'status');
            },
            showBtn: function (dom_id, type = 'show') {
                if (type == 'show') {
                    $(dom_id).show();
                    $(dom_id).prop('disabled', false);
                }
                else {
                    $(dom_id).hide();
                    $(dom_id).prop('disabled', true);
                }
            },
            showMessage: function (style_class, message) {
                $('#shipping-msg').removeClass('success', 'error');
                $('#shipping-msg').addClass(style_class);
                $('#shipping-msg').show();
                $('#shipping-msg-content').text(message);
            },
            shippingAjax: function(url_path, type, method) {
                var data = {
                    'orderId': config.encryptData.order_id,
                    'protectCode': config.encryptData.protect_code
                };

                if (type == 'POST') {
                    data = JSON.stringify(data);
                }

                $.ajax({
                    type: type,
                    url: config.baseUrl + url_path,
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8',
                        'dataType': 'json',
                    },
                    data: data,
                    beforeSend: function(xhr) {
                        $('body').trigger('processStart');
                    },
                    error: function(response) {
                        $('body').trigger('processStop');
                        if (response.code == '2003') {
                            $('#shipping-btns-block').hide();
                            shippingFunction.getShippingTag();
                        }
                        shippingFunction.showMessage('error', response.responseText);
                    },
                    success: function(response) {
                        $('body').trigger('processStop');
                        if (response.code == '0999') {
                            switch (method) {
                                case 'shipment':
                                    let response_data = JSON.parse(response.data);
                                    if (response_data.shipping_method == 'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart'|| response_data.shipping_method == 'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily'|| response_data.shipping_method == 'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife'|| response_data.shipping_method == 'ecpaylogisticcsvokmart_ecpaylogisticcsvokmart'|| response_data.shipping_method == 'ecpaylogistichomepost_ecpaylogistichomepost'|| response_data.shipping_method == 'ecpaylogistichometcat_ecpaylogistichometcat') {
                                        shippingFunction.shippingMethod = response_data.shipping_method;
                                        shippingFunction.getShippingTag();
                                    }
                                    else {
                                        shippingFunction.showBtn('#shipping-block', 'hide');
                                    }
                                    break;
                                case 'status':
                                    if (response.data == '1') {
                                        shippingFunction.showBtn('#print_shipping_btn');
                                    } else {
                                        shippingFunction.showBtn('#create_shipping_btn');
                                        if (shippingFunction.shippingMethod == 'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart'|| shippingFunction.shippingMethod == 'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily'|| shippingFunction.shippingMethod == 'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife'|| shippingFunction.shippingMethod == 'ecpaylogisticcsvokmart_ecpaylogisticcsvokmart') {
                                            shippingFunction.showBtn('#change_store_btn');
                                        }
                                    }
                                    shippingFunction.appendShippingData(config.shippingData);
                                    break;
                                case 'create':
                                    shippingFunction.showBtn('#create_shipping_btn', 'hide');
                                    shippingFunction.showBtn('#change_store_btn', 'hide');
                                    shippingFunction.showBtn('#print_shipping_btn');
                                    shippingFunction.appendShippingData(JSON.parse(response.data));
                                    shippingFunction.showMessage('success', response.msg);
                                    break;
                                case 'print':
                                    var data = JSON.parse(response.data);
                                    $('#print_form').empty();
                                    $('#print_form').append(data.form_print);
                                    $('#ecpay_print').submit();
                                    break; 
                                case 'store':
                                    var data = JSON.parse(response.data);
                                    $('#map_form').empty();
                                    var form = data.form_map;
                                    form = form.replace('target="_self"', 'target="print_popup" onsubmit="window.open(\'about:blank\',\'print_popup\',\'width=1000,height=800\');"')
                                    $('#map_form').append(form);
                                    $('#ecpay-form').submit();
                                    break;
                            }
                        }
                        else shippingFunction.showMessage('error', response.msg);
                    }
                });
            },
            appendShippingData: function(shipping_data) {
                $('#csv_info').empty();

                $('#merchant_trade_no').text(shipping_data.merchant_trade_no);
                $('#all_pay_logistics_id').text(shipping_data.all_pay_logistics_id == null ? '' : shipping_data.all_pay_logistics_id);
                $('#cvs_payment_no').text(shipping_data.cvs_payment_no == null ? '' : shipping_data.cvs_payment_no);
                $('#booking_note').text(shipping_data.booking_note != undefined && shipping_data.booking_note != null ? shipping_data.booking_note : '');

                if(shipping_data.logistics_type == 'CVS' || (shipping_data.cvs_store_id != undefined && shipping_data.cvs_store_id != null)) {
                    $('#csv_info').append('<div class="shipping-description-title">' + $t('CVS Infomation') + '</div>');
                    $('#csv_info').append('<div class="shipping-description-content">');
                    //超商編號
                    $('#csv_info').append('<label>' + $t('CVS Store No：') + '</label><span id="ecpay_cvs_store_id">' + (shipping_data.cvs_store_id == null ? '' : shipping_data.cvs_store_id) + '</span><br>');
                    //超商名稱
                    $('#csv_info').append('<label>' + $t('CVS Store Name：') + '</label></label><span id="ecpay_cvs_store_name">' + (shipping_data.cvs_store_name == null ? '' : shipping_data.cvs_store_name) + '</span><br>');
                }
            }
        }
        
        $(document).ready(function() {
            shippingFunction.getShippingMethod();
        });

        $('#create_shipping_btn').on('click', function() {
            shippingFunction.shippingAjax('rest/V1/ecpay_general/logistic/create_order', 'POST', 'create');
        });

        $('#print_shipping_btn').on('click', function() {
            shippingFunction.shippingAjax('rest/V1/ecpay_general/logistic/print_order', 'POST', 'print');
        });

        $('#change_store_btn').on('click', function() {
            shippingFunction.shippingAjax('rest/V1/ecpay_general/logistic/change_store', 'POST', 'store');
        });
    }
});
           
        