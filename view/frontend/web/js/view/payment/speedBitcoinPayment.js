define([
    "uiComponent",
    "Magento_Checkout/js/model/payment/renderer-list",
], function (Component, rendererList) {
    "use strict";
    rendererList.push({
        type: "speedBitcoinPayment",
        component:
            "Tryspeed_BitcoinPayment/js/view/payment/method-renderer/speedBitcoinPayment",
    });
    /**
     * Add view logic here if needed
     */
    return Component.extend({});
});
