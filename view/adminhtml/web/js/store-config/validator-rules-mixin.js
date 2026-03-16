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
      $.mage.__("Enter Valid Payment Method Name."),
    );
    $.validator.addMethod(
      "validate-paymentMethodDesc",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Payment Method Description."),
    );
    $.validator.addMethod(
      "validate-methodSeq",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Payment Method Sequence."),
    );
    $.validator.addMethod(
      "validate-statementDesc",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Statement Descriptor."),
    );
    $.validator.addMethod(
      "validate-testPk",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Test publishable key."),
    );
    $.validator.addMethod(
      "validate-livePk",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Live publishable key."),
    );
    $.validator.addMethod(
      "validate-testSsk",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Webhook Test Signing Secret Key"),
    );
    $.validator.addMethod(
      "validate-liveSsk",
      function (value) {
        return validateField(value);
      },
      $.mage.__("Enter Valid Webhook Live Signing Secret Key"),
    );
    $.validator.addMethod(
      "validate-ttl-range",
      function (value) {
        if (!isModuleEnabled() || value === "") {
          return true;
        }

        const ttl = parseInt(value);

        if (isNaN(ttl)) {
          return false;
        }

        return ttl >= 300 && ttl <= 86400;
      },
      $.mage.__("TTL must be between 300 and 86400 seconds."),
    );
    $.validator.messages.maxlength = $.mage.__(
      "Maximu 250 characters are allowed.",
    );

    $.validator.messages.minlength = $.mage.__(
      "Minimum 1 character is required.",
    );
    return target;
  };
});
