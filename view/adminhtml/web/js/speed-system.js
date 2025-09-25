require([
  "jquery",
  "Magento_Ui/js/modal/alert",
  "mage/translate",
  "mage/url",
  "domReady!",
], function ($, alert, $t, urlBuilder) {
  let environment = $(
    '[data-ui-id="select-groups-speedbitcoinpayment-groups-basic-fields-speed-mode-value"]'
  ).val();
  if (environment == "test") {
    $("#modeInfoText").text("Test mode");
  } else {
    $("#modeInfoText").text("Live mode");
  }

  if (
    $(
      '[data-ui-id="text-groups-speedbitcoinpayment-groups-basic-fields-descriptor-value"]'
    ).val() == "Magento 2 Store For Speed Plugin"
  ) {
    $(
      '[data-ui-id="text-groups-speedbitcoinpayment-groups-basic-fields-descriptor-value"]'
    ).val("Payment to " + $("#store_name").val());
  }

  const apiUrl = "https://api.tryspeed.com";
  const frontUrl = $("#speed_front_url").val();
  let logo = $("#logo_img").val();

  $('label[for="payment_us_speedBitcoinPayment_basic_logsec1"]').html(
    '<img src="' +
      logo +
      '" alt="Image" style="height: 35px; width: 35px; position: relative; top: -7px; margin-right: 10px;">'
  );
  $('label[for="payment_other_speedBitcoinPayment_basic_logsec1"]').html(
    '<img src="' +
      logo +
      '" alt="Image" style="height: 35px; width: 35px; position: relative; top: -7px; margin-right: 10px;">'
  );
  $('label[for="payment_speedBitcoinPayment_basic_logsec1"]').html(
    '<img src="' +
      logo +
      '" alt="Image" style="height: 35px; width: 35px; position: relative; top: -7px; margin-right: 10px;">'
  );

  async function callWebhookValidator(publicId, privateId, url, mode) {
    try {
      $("body").loader("show");

      const authToken = btoa(publicId);
      const response = await fetch(apiUrl + "/webhooks/verify-secret", {
        method: "POST",
        headers: {
          authorization: "Basic " + authToken,
          "speed-version": "2022-10-15",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          url: url,
          secret: privateId,
        }),
      });

      const resStatus = response.status;
      const apiRes = await response.json();

      if (resStatus != 200) {
        const errorResponse = apiRes.errors;
        alert({
          title: $t("Connection failed!"),
          content: $t(errorResponse[0].message),
        });
      } else {
        if (apiRes.exists) {
          if (apiRes.status.toLowerCase() === "active") {
            alert({
              title: $t("Connection Successful!"),
              content: $t("Your " + mode + " Speed Credentials are validated"),
            });
          } else {
            alert({
              title: $t("Connection failed!"),
              content: $t(
                "To mark an order as paid using a webhook, the Speed webhook endpoint URL needs to be activated"
              ),
            });
          }
        } else {
          alert({
            title: $t("Connection failed!"),
            content: $t(
              "We could not find a matching webhook for the provided URL and signing secret. Please verify both and try again."
            ),
          });
        }
      }
    } catch (error) {
      let errorMsg =
        error && error.message ? error.message : $t("Unknown error occurred.");
      alert({
        title: $t("Connection failed!"),
        content: $t(errorMsg),
      });
    } finally {
      $("body").loader("hide");
    }
  }

  window.speedTestApiValidator = function () {
    let publicId = "",
      privateId = "";
    publicId = $(
      '[data-ui-id="text-groups-speedbitcoinpayment-groups-test-fields-speed-test-pk-value"]'
    ).val();
    privateId = $(
      '[data-ui-id="text-groups-speedbitcoinpayment-groups-test-fields-speed-test-sk-value"]'
    ).val();
    $("#payment_us_speedBitcoinPayment_test_speed_test_pk").valid();
    $("#payment_us_speedBitcoinPayment_test_speed_test_sk").valid();
    /* Basic field validation */
    var errors = [];

    if (!publicId) {
      errors.push($t("Please enter a Test Publishable  Key"));
    }

    if (!privateId) {
      errors.push($t("Please enter webhook Test Signing Secret key."));
    }

    if (errors.length > 0) {
      return false;
    }
    if (/^pk_test_/.test(publicId)) {
      callWebhookValidator(
        publicId,
        privateId,
        $("#speed_test_webhook_url").val(),
        "Test"
      );
    } else {
      alert({
        title: $t("Connection failed!"),
        content: $t(
          "Please ensure that you have entered a valid Test Publishable Key."
        ),
      });
    }
  };

  window.speedLiveApiValidator = function () {
    let publicId = "",
      privateId = "";
    publicId = $(
      '[data-ui-id="text-groups-speedbitcoinpayment-groups-live-fields-speed-live-pk-value"]'
    ).val();
    privateId = $(
      '[data-ui-id="text-groups-speedbitcoinpayment-groups-live-fields-speed-live-sk-value"]'
    ).val();
    $("#payment_us_speedBitcoinPayment_live_speed_live_pk").valid();
    $("#payment_us_speedBitcoinPayment_live_speed_live_sk").valid();
    /* Basic field validation */
    var errors = [];

    if (!publicId) {
      errors.push($t("Please enter a Live Publishable  Key"));
    }

    if (!privateId) {
      errors.push($t("Please enter webhook Live Signing Secret key."));
    }

    if (errors.length > 0) {
      return false;
    }

    if (/^pk_live_/.test(publicId)) {
      callWebhookValidator(
        publicId,
        privateId,
        $("#speed_live_webhook_url").val(),
        "Live"
      );
    } else {
      alert({
        title: $t("Connection failed!"),
        content: $t(
          "Please ensure that you have entered a valid Live Publishable Key."
        ),
      });
    }
  };

  window.copyTestSpeedWebhookUrl = function () {
    const elem = document.createElement("textarea");
    elem.value = $("#speed_test_webhook_url").val();
    document.body.appendChild(elem);
    elem.select();
    document.execCommand("copy");
    document.body.removeChild(elem);
    $(".webhook-test-url-success").removeClass("d-none");
    setTimeout(function () {
      $(".webhook-test-url-success").addClass("d-none");
    }, 700);
  };

  window.copyLiveSpeedWebhookUrl = function () {
    const elem = document.createElement("textarea");
    elem.value = $("#speed_live_webhook_url").val();
    document.body.appendChild(elem);
    elem.select();
    document.execCommand("copy");
    document.body.removeChild(elem);
    $(".webhook-live-url-success").removeClass("d-none");
    setTimeout(function () {
      $(".webhook-live-url-success").addClass("d-none");
    }, 700);
  };

  $("#payment_us_speedBitcoinPayment_basic_speed_mode").on(
    "change",
    function () {
      let environment = $(
        '[data-ui-id="select-groups-speedbitcoinpayment-groups-basic-fields-speed-mode-value"]'
      ).val();
      if (environment == "test") {
        $("#modeInfoText").text("Test mode");
      } else {
        $("#modeInfoText").text("Live mode");
      }
    }
  );
});
