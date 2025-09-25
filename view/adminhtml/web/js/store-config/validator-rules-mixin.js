define(["jquery"], function ($) {
  "use strict";
  return function (target) {
    $.validator.addMethod(
      "validate-paymentMethod",
      function (value) {
        if (value != "") {
          return true;
        } else {
          return false;
        }
      },
      $.mage.__("Enter Valid Payment Method Name.")
    );
    $.validator.addMethod(
      "validate-paymentMethodDesc",
      function (value) {
        if (value != "") {
          return true;
        } else {
          return false;
        }
      },
      $.mage.__("Enter Payment Method Descriprtion.")
    );
    $.validator.addMethod(
      "validate-methodSeq",
      function (value) {
        if (value != "") {
          return true;
        } else {
          return false;
        }
      },
      $.mage.__("Enter Payment Method Sequence.")
    );
    $.validator.addMethod(
      "validate-statementDesc",
      function (value) {
        if (value != "") {
          return true;
        } else {
          return false;
        }
      },
      $.mage.__("Enter Statement Descriptor.")
    );
    $.validator.addMethod(
      "validate-testPk",
      function (value) {
        if (value != "") {
          return true;
        } else {
          return false;
        }
      },
      $.mage.__("Enter Test publishable key.")
    );
    $.validator.addMethod(
      "validate-livePk",
      function (value) {
        if (value != "") {
          return true;
        } else {
          return false;
        }
      },
      $.mage.__("Enter Live publishable key.")
    );
    $.validator.addMethod(
      "validate-testSsk",
      function (value) {
        if (value != "") {
          return true;
        } else {
          return false;
        }
      },
      $.mage.__("Enter Webhook Test Signing Secret Key")
    );
    $.validator.addMethod(
      "validate-liveSsk",
      function (value) {
        if (value != "") {
          return true;
        } else {
          return false;
        }
      },
      $.mage.__("Enter Valid Webhook Live Signing Secret Key")
    );
    return target;
  };
});
