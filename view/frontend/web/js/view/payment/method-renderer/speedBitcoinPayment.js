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
  "Magento_CheckoutAgreements/js/model/agreement-validator",
  "Magento_CheckoutAgreements/js/model/agreements-assigner",
  "Magento_CheckoutAgreements/js/view/checkout-agreements",
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
  $t,
  agreementValidator,
  agreementsAssigner,
  CheckoutAgreements
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

    webhookValidated: false,

    initObservable: function () {
      this._super().observe(["logoImage", "shouldDisplayImage"]);

      var self = this;
      var currentTotals = quote.totals();
      var currentBillingAddress = quote.billingAddress();
      var currentShippingAddress = quote.shippingAddress();

      quote.billingAddress.subscribe(function (address) {
        if (!address) return;
        if (self.isAddressSame(address, currentBillingAddress)) return;
        currentBillingAddress = address;
      });

      quote.shippingAddress.subscribe(function (address) {
        if (!address) return;
        if (self.isAddressSame(address, currentShippingAddress)) return;
        currentShippingAddress = address;
      });

      quote.totals.subscribe(function (totals) {
        var newSegs = (totals && totals.total_segments) || [];
        var oldSegs = (currentTotals && currentTotals.total_segments) || [];
        if (JSON.stringify(newSegs) === JSON.stringify(oldSegs)) return;
        currentTotals = totals;
      });

      this.logoImage(
        window.checkoutConfig.payment.speedBitcoinPayment.logoImage
      );
      this.shouldDisplayImage(
        window.checkoutConfig.payment.speedBitcoinPayment.logoDisplay == 1
      );

      self.isPlaceOrderActionAllowed(false);

      return this;
    },

    selectPaymentMethod: function () {
      this._super();

      var self = this;

      $.ajax({
        url: urlMaker.build("tryspeed/webhook/check"),
        type: "GET",
        showLoader: true,
        success: function (response) {
          if (!response.active) {
            self.webhookValidated = false;

            self.messageContainer.addErrorMessage({
              message: $t(
                "Speed Bitcoin Payment method is currently unavailable. Please choose a different payment option."
              ),
            });

            $('input[name="payment[method]"]').prop("checked", false);

            if (typeof self.deselectPaymentMethod === "function") {
              self.deselectPaymentMethod();
            }
            self.isPlaceOrderActionAllowed(false);
          } else {
            self.webhookValidated = true;
            self.isPlaceOrderActionAllowed(true);
          }
        },
        error: function () {
          self.webhookValidated = false;

          self.messageContainer.addErrorMessage({
            message: $t("Unable to verify Webhook status."),
          });

          $('input[name="payment[method]"]').prop("checked", false);

          if (typeof self.deselectPaymentMethod === "function") {
            self.deselectPaymentMethod();
          }
          self.isPlaceOrderActionAllowed(false);
        },
      });

      return true;
    },

    /**
     * Compare two addresses to detect changes
     */
    isAddressSame: function (address1, address2) {
      var a = this.stringifyAddress(address1);
      var b = this.stringifyAddress(address2);
      return a == b;
    },

    showHideLogo: function (flag) {
      if (flag == 1) $("#image-container").show();
      else $("#image-container").hide();
    },

    getPaymentDescription: function () {
      return window.checkoutConfig.payment.speedBitcoinPayment.description;
    },

    stringifyAddress: function (address) {
      if (!address) return null;
      return JSON.stringify({
        countryId: address.countryId || "",
        region: address.region || "",
        city: address.city || "",
        postcode: address.postcode || "",
      });
    },

    placeCheckoutOrder: function () {
      var self = this;

      if (
        !quote.paymentMethod() ||
        quote.paymentMethod().method !== this.getCode()
      ) {
        this.messageContainer.addErrorMessage({
          message: $t(
            "Please select Speed Bitcoin Payment before placing the order."
          ),
        });
        return false;
      }

      if (!this.webhookValidated) {
        this.messageContainer.addErrorMessage({
          message: $t(
            "Speed Bitcoin Payment method is currently unavailable. Please choose a different payment option."
          ),
        });
        self.isPlaceOrderActionAllowed(false);
        return false;
      }

      if (!agreementValidator.validate()) {
        return false;
      }

      var paymentData = self.getData();

      try {
        if (
          agreementsAssigner &&
          typeof agreementsAssigner.assign === "function"
        ) {
          agreementsAssigner.assign(paymentData);
        } else if (typeof agreementsAssigner === "function") {
          agreementsAssigner(paymentData);
        } else {
          var selectedIds = [];
          if (CheckoutAgreements && CheckoutAgreements.agreements) {
            CheckoutAgreements.agreements().forEach(function (a) {
              var id = a.agreement_id || a.id || a.agreementId || null;
              if (a.checked && id) selectedIds.push(id);
            });
          }

          paymentData.additional_data = paymentData.additional_data || {};
          paymentData.additional_data.agreement_ids = selectedIds;
        }
      } catch (e) {
        console.error("agreements assignment failed:", e);
      }

      self.isPlaceOrderActionAllowed(false);
      fullScreenLoader.startLoader();

      var currentTotals = quote.totals() || {};
      var quoteId = quote.getQuoteId();

      var postdata = {
        currency: currentTotals.base_currency_code,
        amount: currentTotals.base_grand_total,
        quoteid: quoteId,
      };

      placeOrderAction(paymentData, self.messageContainer)
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
            error: function () {
              fullScreenLoader.stopLoader();
              self.messageContainer.addErrorMessage({
                message: $t("Payment redirection failed."),
              });
            },
          }).always(function () {
            self.isPlaceOrderActionAllowed(true);
          });
        })
        .fail(function () {
          fullScreenLoader.stopLoader();
          self.isPlaceOrderActionAllowed(true);
        });

      return false;
    },

    showError: function (message, element) {
      if (element && element.scrollIntoView) {
        element.scrollIntoView({ behavior: "smooth", block: "center" });
      }
      this.messageContainer.addErrorMessage({ message: message });
    },
  });
});
