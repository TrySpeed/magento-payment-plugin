define([
    "ko",
    "jquery",
    "Magento_Checkout/js/model/url-builder",
    "mage/url",
    "Magento_Checkout/js/model/quote",
    "Magento_Checkout/js/action/place-order",
    "Magento_Checkout/js/model/full-screen-loader",
    "Magento_Ui/js/model/messageList",
    "Magento_Customer/js/customer-data",
    "Magento_Checkout/js/view/payment/default",
    "mage/translate",
    "domReady!",
], function (
    ko,
    $,
    urlBuilder,
    urlMaker,
    quote,
    placeOrderAction,
    fullScreenLoader,
    globalMessageList,
    customerData,
    Component,
    $t
) {
    "use strict";

    return Component.extend({
        defaults: {
            self: this,
            template: "Tryspeed_BitcoinPayment/payment/form",
            code: "speedBitcoinPayment",
            logoImage: null,
            logoStyle: null,
        },
        initObservable: function () {
            this._super().observe(["logoImage"]);

            var self = this;
            var currentTotals = quote.totals();
            var currentBillingAddress = quote.billingAddress();
            var currentShippingAddress = quote.shippingAddress();
            quote.billingAddress.subscribe(function (address) {
                if (!address) {
                    return;
                }

                if (self.isAddressSame(address, currentBillingAddress)) {
                    return;
                }

                currentBillingAddress = address;
            }, this);

            quote.shippingAddress.subscribe(function (address) {
                if (!address) {
                    return;
                }

                if (self.isAddressSame(address, currentShippingAddress)) {
                    return;
                }

                currentShippingAddress = address;
            }, this);

            quote.totals.subscribe(function (totals) {
                if (
                    JSON.stringify(totals.total_segments) ==
                    JSON.stringify(currentTotals.total_segments)
                ) {
                    return;
                }

                currentTotals = totals;
            }, this);

            this.logoImage =
                window.checkoutConfig.payment.speedBitcoinPayment.logoImage;
            if (
                window.checkoutConfig.payment.speedBitcoinPayment.logoDisplay ==
                1
            ) {
                this.shouldDisplayImage = ko.observable(true);
            } else {
                this.shouldDisplayImage = ko.observable(false);
            }
            return this;
        },
        isAddressSame: function (address1, address2) {
            var a = this.stringifyAddress(address1);
            var b = this.stringifyAddress(address2);

            return a == b;
        },
        showHideLogo: function (flag) {
            if (flag == 1) {
                $("#image-container").show();
            } else {
                $("#image-containerimage-container").hide();
            }
        },
        getPaymentDescription: function () {
            return window.checkoutConfig.payment.speedBitcoinPayment
                .description;
        },
        stringifyAddress: function (address) {
            if (typeof address == "undefined" || !address) {
                return null;
            }

            return JSON.stringify({
                countryId:
                    typeof address.countryId != "undefined"
                        ? address.countryId
                        : "",
                region:
                    typeof address.region != "undefined" ? address.region : "",
                city: typeof address.city != "undefined" ? address.city : "",
                postcode:
                    typeof address.postcode != "undefined"
                        ? address.postcode
                        : "",
            });
        },
        placeCheckoutOrder: function () {
            var self = this;
            fullScreenLoader.startLoader();
            var currentTotals = quote.totals();
            var quoteId = quote.getQuoteId();
            var postdata = {};
            postdata.currency = currentTotals.base_currency_code;
            postdata.amount = currentTotals.base_grand_total;
            postdata.quoteid = quoteId;
            placeOrderAction(self.getData(), self.messageContainer)
            .done(function () {
                $.ajax({
                    url: urlMaker.build("tryspeed/payment"),
                    type: "POST",
                    data: postdata,
                    dataType: "json",
                    showLoader: true,
                    success: function (response) {
                        fullScreenLoader.stopLoader();
                        window.location.href = response.redirect_url;
                    },
                    error: function (error) {
                    },
                });
            })
        },
        showError: function (message) {
            document.getElementById("actions-toolbar").scrollIntoView(true);
            this.messageContainer.addErrorMessage({ message: message });
        },
    });
});
