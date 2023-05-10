define([
    'jquery',
    'mage/translate'
], function($, $t) {
    'use strict';

    return function(config, element) {
        var invoiceFunction = {
            getInvoiceModuleEnable: function() {
                if (!config.invoiceModuleEnable) {
                    $('#invoice-btns-block').hide();
                }
                this.getInvoiceTag();
            },
            getInvoiceTag: function() {
                this.invoiceAjax('rest/V1/ecpay_general/invoice/get_invoice_tag', 'GET', 'status');
            },
            showBtn: function (dom_id, type = 'show') {
                if (type == 'show') {
                    $(dom_id).show();
                    $(dom_id).removeClass('disabled');
                }
                else {
                    $(dom_id).hide();
                    $(dom_id).addClass('disabled');
                }
            },
            showMessage: function (style_class, message) {
                $('#invoice-msg').removeClass('success', 'error');
                $('#invoice-msg').addClass(style_class);
                $('#invoice-msg').show();
                $('#invoice-msg-content').text(message)
            },
            invoiceAjax: function(url_path, type, method) {
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
                        if (response.code == '1004' || response.code == '1003' || response.code == '1002') {
                            var response = response.json();
                            invoiceFunction.showMessage('error', response.msg)
                        }
                        else {
                            invoiceFunction.showMessage('error', response.responseText)
                        }
                    },
                    success: function(response) {
                        $('body').trigger('processStop');

                        if (response.code == '0999') {
                            switch (method) {
                                case 'status':
                                    if (response.data == '1') {
                                        $('#invoice_info').show();
                                        invoiceFunction.showBtn('#create_invoice_btn', 'hide');
                                        invoiceFunction.showBtn('#invalid_invoice_btn');

                                        invoiceFunction.appendInvoiceData(config.invoiceData)
                                    } else {
                                        $('#invoice_info').hide();
                                        invoiceFunction.showBtn('#create_invoice_btn');
                                        invoiceFunction.showBtn('#invalid_invoice_btn', 'hide');
                                    }
                                    break;
                                case 'create':
                                    $('#invoice_info').show();
                                    invoiceFunction.showBtn('#invalid_invoice_btn');
                                    invoiceFunction.showBtn('#create_invoice_btn', 'hide');

                                    invoiceFunction.appendInvoiceData(JSON.parse(response.data))
                                    invoiceFunction.showMessage('success', response.msg)
                                    break;
                                case 'invalid':
                                    $('#invoice_info').hide();
                                    invoiceFunction.showBtn('#create_invoice_btn');
                                    invoiceFunction.showBtn('#invalid_invoice_btn', 'hide');

                                    $('#invoice_no').text('')
                                    $('#invoice_date').text('')
                                    $('#invoice_random_num').text('')
                                    $('#invoice_issue_type').text('')
                                    $('#invoice_od_sob').text('')
                                    $('#invoice_type').text('')
                                    $('#invoice_codes').empty();
                                    invoiceFunction.showMessage('success', response.msg)
                                    break;
                            }
                        }
                        else invoiceFunction.showMessage('error', response.msg)
                    }
                });
            },
            appendInvoiceData: function(invoice_data) {
                $('#invoice_issue_type').text(invoice_data.ecpay_invoice_issue_type == '1' ? $t('Invoicing') : $t('Delayed Invoicing'))
                $('#invoice_no').text(invoice_data.ecpay_invoice_number == null ? '' : invoice_data.ecpay_invoice_number)
                $('#invoice_date').text(invoice_data.ecpay_invoice_date == null ? '' : invoice_data.ecpay_invoice_date)
                $('#invoice_random_num').text(invoice_data.ecpay_invoice_random_number == null ? '' : invoice_data.ecpay_invoice_random_number)
                $('#invoice_od_sob').text(invoice_data.ecpay_invoice_od_sob)

                switch (invoice_data.ecpay_invoice_type) {
                    case '公司':
                        $('#invoice_type').text($t('公司'))
                        break;
                    case '捐贈':
                        $('#invoice_type').text($t('捐贈'))
                        break;
                    default:
                        $('#invoice_type').text($t('個人'))
                        break;
                }

                $('#invoice_codes').empty();
                //捐贈碼
                if (invoice_data.ecpay_invoice_love_code !== undefined && invoice_data.ecpay_invoice_love_code !== null) $('#invoice_codes').append('<label>' + $t('Donation Code') + '：</label><span id="invoice_love_code"> ' + invoice_data.ecpay_invoice_love_code + '</span><br>')
                // 公司行號
                if (invoice_data.ecpay_invoice_customer_company !== undefined && invoice_data.ecpay_invoice_customer_company !== null) $('#invoice_codes').append('<label>' + $t('Company Name') + '：</label><span id="invoice_customer_company"> ' + invoice_data.ecpay_invoice_customer_company + '</span><br>')
                //統一編號
                if (invoice_data.ecpay_invoice_customer_identifier !== undefined && invoice_data.ecpay_invoice_customer_identifier !== null) $('#invoice_codes').append('<label>' + $t('Uniform Numbers') + '：</label><span id="invoice_customer_identifier"> ' + invoice_data.ecpay_invoice_customer_identifier + '</span><br>')
                //載具編號
                if (invoice_data.ecpay_invoice_carruer_num !== undefined && invoice_data.ecpay_invoice_carruer_num !== null) $('#invoice_codes').append('<label>' + $t('Carrier Code') + '：</label><span id="invoice_carruer_num"> ' + invoice_data.ecpay_invoice_carruer_num + '</span><br>')
            }
        }

        $(document).ready(function() {
            invoiceFunction.getInvoiceModuleEnable();
        });

        $('#create_invoice_btn').on('click', function() {
            invoiceFunction.invoiceAjax('rest/V1/ecpay_general/invoice/create_invoice', 'POST', 'create');
        });

        $('#invalid_invoice_btn').on('click', function() {
            invoiceFunction.invoiceAjax('rest/V1/ecpay_general/invoice/invalid_invoice', 'POST', 'invalid');
        });
    }
});