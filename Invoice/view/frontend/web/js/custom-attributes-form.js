define([
    'ko',
    'uiComponent',
    'jquery',
    'Magento_Customer/js/model/customer'
], function (ko, Component, $, customer) {
    'use strict';

    return Component.extend ({
        initialize: function () {
            this._super();

            this.is_logged_in = this.isLoggedIn();
            this.validation_fields = ["customer_company", "customer_identifier", "carruer_num", "love_code"];

            // 填入預設捐贈碼
            this.defaultLoveCode = window.checkoutConfig.default_love_code;

            // 定義目前載具類型及選項
            this.currentCarruerType = ko.observable("Paper Invoice");
            this.carruerTypes = ko.observableArray([
                { name: "Paper Invoice", value: "0", active: ko.observable(true) },
                { name: "Cloud Invoice", value: "1", active: ko.observable(true) },
                { name: "Natural Person Certificate", value: "2", active: ko.observable(true) },
                { name: "Mobile Barcode", value: "3", active: ko.observable(true) }
            ])

            // 定義目前發票類型及選項
            this.currentInvoiceType = ko.observable("Individual");
            this.invoiceTypes = ko.observableArray(
                [
                    { name: "Individual", value: "p", active: true },
                    { name: "Company", value: "c", active: true },
                    { name: "Donation", value: "d", active: true }
                ]
            );

            // 輸入後即時移除錯誤欄位 error msg
            this.removeErrorMes = (field) => {
                $("#ecpay_invoice_" + field).removeAttr("style");
                $("#" + field + "_error").empty();
            }

            // 監聽 fields (公司行號、公司統編、載具編號、捐贈碼)
            this.customer_company.subscribe((newValue) => {
                this.removeErrorMes('customer_company');    
            });
            this.customer_identifier.subscribe((newValue) => {
                this.removeErrorMes('customer_identifier');    
            });
            this.carruer_num.subscribe((newValue) => {
                this.removeErrorMes('carruer_num');    
            });
            this.love_code.subscribe((newValue) => {
                this.removeErrorMes('love_code');    
            });

            return this;
        },
        defaults: {
            // 是否顯示欄位、定義欄位預設值
            showCarruerType: ko.observable(true),
            showCustomerIdentifier: ko.observable(false),
            showCustomerCompany: ko.observable(false),
            showLoveCode: ko.observable(false),
            showCarruerNum: ko.observable(false),
            customer_identifier: ko.observable(''),
            customer_company: ko.observable(''),
            love_code: ko.observable(''),
            carruer_num: ko.observable(''),
        },
        selectCarruerType: function (obj, event) {
            // 變更選定載具類型
            this.currentCarruerType(event.target.value);

            // 重置元件
            this.refreshData();

            // 公司發票
            if (this.currentInvoiceType() === "c") {
                this.showCustomerIdentifier(true);
                this.showCustomerCompany(true);
                // 紙本發票公司行號必填，載具非必填
                if (this.currentCarruerType() === "0") {
                    $('#ecpay_invoice_customer_company_div').addClass('_required')
                }
                else {
                    $('#ecpay_invoice_customer_company_div').removeClass('_required')
                }
            }

            // 顯示載具輸入框
            if (this.currentCarruerType() === "2" || this.currentCarruerType() === "3") {
                this.showCarruerNum(true)
            }
        },
        selectInvoiceType: function (obj, event) {
            // 變更選定InvoiceType
            this.currentInvoiceType(event.target.value);

            // 重置元件
            this.refreshData();

            // 更動顯示元件
            switch (this.currentInvoiceType()) {
                case "c":
                    this.showCustomerIdentifier(true);
                    this.showCustomerCompany(true);
                    this.showCarruerType(true);
                    // 重置載具選項、公司行號必填
                    $('#ecpay_invoice_carruer_type option:first').prop("selected", true)
                    $('#ecpay_invoice_customer_company_div').addClass('_required')

                    // 關閉自然人憑證選項
                    const option = this.carruerTypes().find(item => item.value == '2');
                    if (option) option.active(false);

                    break;

                case "d":
                    this.showLoveCode(true);
                    this.showCarruerType(false);
                    this.love_code(this.defaultLoveCode);
                    break;

                default:
                    this.showCarruerType(true);
                    // 重置載具選項
                    $('#ecpay_invoice_carruer_type option:first').prop("selected", true)
            }
        },
        refreshData: function () {
            this.showCarruerType(true);
            this.showCustomerIdentifier(false);
            this.showCustomerCompany(false);
            this.showLoveCode(false);
            this.showCarruerNum(false);
            this.love_code('');
            this.customer_identifier('');
            this.customer_company('')
            this.carruer_num('')
            
            // 移除所有欄位 error msg
            $.each(this.validation_fields, function(index, value) {
                $("#ecpay_invoice_" + value).removeAttr("style");
                $("#" + value + "_error").empty();
            })

            // 開啟自然人憑證選項
            const option = this.carruerTypes().find(item => item.value == '2');
            if (option) option.active(true);

        },
        isLoggedIn: function () {
            return customer.isLoggedIn();
        }
    });
});