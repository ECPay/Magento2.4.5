define([
    'ko',
    'uiComponent',
    'jquery',
    'Magento_Customer/js/model/customer'
], function (ko, Component, $, customer) {
    'use strict';

    return Component.extend ({
        initialize: function () {
            this.is_logged_in = this.isLoggedIn();

            this.defaultLoveCode = window.checkoutConfig.default_love_code;

            this.currentCarruerType = ko.observable("Paper Invoice");
            this.carruerTypes = ko.observableArray([
                { name: "Paper Invoice", value: "0", active: true },
                { name: "Cloud Invoice", value: "1", active: true },
                { name: "Natural Person Certificate", value: "2", active: true },
                { name: "Mobile Barcode", value: "3", active: true }
            ])

            this.currentInvoiceType = ko.observable("Individual");
            this.invoiceTypes = ko.observableArray(
                [
                    { name: "Individual", value: "p", active: true },
                    { name: "Company", value: "c", active: true },
                    { name: "Donation", value: "d", active: true }
                ]
            );

            this.showCarruerType = ko.observable(true);
            this.showCustomerIdentifier = ko.observable(false);
            this.showCustomerCompany = ko.observable(false);
            this.showLoveCode = ko.observable(false);
            this.showCarruerNum = ko.observable(false);

            this.customer_identifier = ko.observable('');
            this.customer_company = ko.observable('');
            this.love_code = ko.observable('');
            this.carruer_num = ko.observable('');

            this._super();
            return this;
        },
        selectCarruerType: function (obj, event) {
            // 變更選定CarruerType
            this.currentCarruerType(event.target.value);

            // 重置元件
            this.refreshData();
            
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
                    this.showCarruerType(false);
                    break;
                case "d":
                    this.showLoveCode(true);
                    this.showCarruerType(false);
                    this.love_code(this.defaultLoveCode);
                    break;
                default:
                    this.showCarruerType(true);
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
            this.carruer_num('')
        },
        isLoggedIn: function () {
            return customer.isLoggedIn();
        }
    });
});