define(["jquery"], function ($) {
  "use strict";
  return function (target) {
    function isModuleEnabled() {
      const activeField = $("#payment_us_speedBitcoinPayment_basic_active");

      if (!activeField.length) {
        return false;
      }

      return activeField.val() === "1";
    }
    function validateField(value) {
      if (!isModuleEnabled()) {
        return true;
      }
      return value !== "";
    }
    $.validator.addMethod(
      "validate-paymentMethod",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Valid Payment Method Name.")
    );
    $.validator.addMethod(
      "validate-paymentMethodDesc",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Payment Method Descriprtion.")
    );
    $.validator.addMethod(
      "validate-methodSeq",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Payment Method Sequence.")
    );
    $.validator.addMethod(
      "validate-statementDesc",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Statement Descriptor.")
    );
    $.validator.addMethod(
      "validate-testPk",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Test publishable key.")
    );
    $.validator.addMethod(
      "validate-livePk",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Live publishable key.")
    );
    $.validator.addMethod(
      "validate-testSsk",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Webhook Test Signing Secret Key")
    );
    $.validator.addMethod(
      "validate-liveSsk",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Valid Webhook Live Signing Secret Key")
    );
    return target;
  };
});
