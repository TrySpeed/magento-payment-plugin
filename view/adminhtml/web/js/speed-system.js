require(
    ['jquery', 'Magento_Ui/js/modal/alert', 'mage/translate', 'mage/url', 'domReady!'], function ($, alert, $t, urlBuilder) {
        let environment = $('[data-ui-id="select-groups-speedbitcoinpayment-groups-basic-fields-speed-mode-value"]').val()
        if (environment == 'test') {
            $('#modeInfoText').text('Test mode');
        } else {
            $('#modeInfoText').text('Live mode');
        }
        if($('[data-ui-id="text-groups-speedbitcoinpayment-groups-basic-fields-descriptor-value"]').val() == 'Magento 2 Store For Speed Plugin'){
            $('[data-ui-id="text-groups-speedbitcoinpayment-groups-basic-fields-descriptor-value"]').val('Payment to '+$('#store_name').val());
        }
        const apiUrl = 'https://api.tryspeed.com';
        const frontUrl = $("#speed_front_url").val();
        let logo = $('#logo_img').val();
        $('label[for="payment_us_speedBitcoinPayment_basic_logsec1"]').html('<img src="'+logo+'" alt="Image" style="height: 35px; width: 35px; position: relative; top: -7px; margin-right: 10px;">');
        $('label[for="payment_other_speedBitcoinPayment_basic_logsec1"]').html('<img src="'+logo+'" alt="Image" style="height: 35px; width: 35px; position: relative; top: -7px; margin-right: 10px;">');
        $('label[for="payment_speedBitcoinPayment_basic_logsec1"]').html('<img src="'+logo+'" alt="Image" style="height: 35px; width: 35px; position: relative; top: -7px; margin-right: 10px;">');
        
        window.speedTestApiValidator = function () {
            let publicId = '', privateId = '';
            publicId = $('[data-ui-id="text-groups-speedbitcoinpayment-groups-test-fields-speed-test-pk-value"]').val();
            privateId = $('[data-ui-id="text-groups-speedbitcoinpayment-groups-test-fields-speed-test-sk-value"]').val();
            $('#payment_us_speedBitcoinPayment_test_speed_test_pk').valid();
            $('#payment_us_speedBitcoinPayment_test_speed_test_sk').valid();
            /* Basic field validation */
            var errors = [];

            if (!publicId) {
                errors.push($t('Please enter a Test Publishable  Key'));
            }

            if (!privateId) {
                errors.push($t('Please enter webhook Test Signing Secret key.'));
            }

            if (errors.length > 0) {
                return false;
            }
            if(/^pk_test_/.test(publicId)){
                const authToken = btoa(publicId);
                $.ajax(
                    {
                        type: "POST",
                        url: apiUrl+"/webhooks/verify-secret",
                        headers: { authorization: "Basic " + authToken, 'speed-version': '2022-10-15' },
                        data: JSON.stringify(
                            {
                                url: $("#speed_test_webhook_url").val(),
                                secret: privateId,
                            }
                        ),
                        contentType: "application/json",
                        dataType: "text",
                        showLoader: true
                    }
                ).done(
                    function (res) {
                        let successRes = JSON.parse(res);
                        if(successRes.exists){
                            if(successRes.status == 'active'){
                                alert(
                                    {
                                        title: $t('Connection Successfull!'),
                                        content: $t('Your Test Speed Credentials are validated')
                                    }
                                );
                            }else{
                                alert(
                                    {
                                        title: $t('Connection failed!'),
                                        content: $t('To mark an order as paid using a webhook, the speed webhook endpoint URL needs to be activated')
                                    }
                                );
                            }
                        }
                    }
                ).fail(
                    function (error) {
                        let erroeRes = JSON.parse(error.responseText);
                        console.log(erroeRes);
                        alert(
                            {
                                title: $t('Connection failed!'),
                                content: $t(erroeRes.errors[0].message)
                            }
                        );
                    }
                );
            }else{
                alert(
                    {
                        title: $t('Connection failed!'),
                        content: $t('Please ensure that you have entered a valid Test Publishable Key.')
                    }
                );
            }
        };

        window.speedLiveApiValidator = function () {
            let publicId = '', privateId = '';
            publicId = $('[data-ui-id="text-groups-speedbitcoinpayment-groups-live-fields-speed-live-pk-value"]').val();
            privateId = $('[data-ui-id="text-groups-speedbitcoinpayment-groups-live-fields-speed-live-sk-value"]').val();
            $('#payment_us_speedBitcoinPayment_live_speed_live_pk').valid();
            $('#payment_us_speedBitcoinPayment_live_speed_live_sk').valid();
            /* Basic field validation */
            var errors = [];

            if (!publicId) {
                errors.push($t('Please enter a Live Publishable  Key'));
            }

            if (!privateId) {
                errors.push($t('Please enter webhook Live Signing Secret key.'));
            }

            if (errors.length > 0) {
                return false;
            }
            
            if(/^pk_live_/.test(publicId)){
                const authToken = btoa(publicId);
                $.ajax(
                    {
                        type: "POST",
                        url: apiUrl+"/webhooks/verify-secret",
                        headers: { authorization: "Basic " + authToken, 'speed-version': '2022-10-15'},
                        data: JSON.stringify(
                            {
                                url: $("#speed_live_webhook_url").val(),
                                secret: privateId,
                            }
                        ),
                        contentType: "application/json",
                        dataType: "text",
                        showLoader: true
                    }
                ).done(
                    function (res) {
                        let successRes = JSON.parse(res);
                        if(successRes.exists){
                            if(successRes.status == 'active'){
                                alert(
                                    {
                                        title: $t('Connection Successfull!'),
                                        content: $t('Your Live Speed Credentials are validated')
                                    }
                                );
                            }else{
                                alert(
                                    {
                                        title: $t('Connection failed!'),
                                        content: $t('To mark an order as paid using a webhook, the speed webhook endpoint URL needs to be activated')
                                    }
                                );
                            }
                        }
                    }
                ).fail(
                    function (error) {
                        let erroeRes = JSON.parse(error.responseText);
                        alert(
                            {
                                title: $t('Connection failed!'),
                                content: $t(erroeRes.errors[0].message)
                            }
                        );
                    }
                )
            }else{
                alert(
                    {
                        title: $t('Connection failed!'),
                        content: $t('Please ensure that you have entered a valid Live Publishable Key.')
                    }
                );
            }
        };

        window.copyTestSpeedWebhookUrl = function () {
            const elem = document.createElement('textarea');
            elem.value = $("#speed_test_webhook_url").val()
            document.body.appendChild(elem);
            elem.select();
            document.execCommand('copy');
            document.body.removeChild(elem);
            $('.webhook-test-url-success').removeClass('d-none');
            setTimeout(
                function () {
                    $('.webhook-test-url-success').addClass('d-none');
                }, 700
            )
        }

        window.copyLiveSpeedWebhookUrl = function () {
            const elem = document.createElement('textarea');
            elem.value = $("#speed_live_webhook_url").val()
            document.body.appendChild(elem);
            elem.select();
            document.execCommand('copy');
            document.body.removeChild(elem);
            $('.webhook-live-url-success').removeClass('d-none');
            setTimeout(
                function () {
                    $('.webhook-live-url-success').addClass('d-none');
                }, 700
            )
        }

        $('#payment_us_speedBitcoinPayment_basic_speed_mode').on('change', function(){
            let environment = $('[data-ui-id="select-groups-speedbitcoinpayment-groups-basic-fields-speed-mode-value"]').val()
            if (environment == 'test') {
                $('#modeInfoText').text('Test mode');
            } else {
                $('#modeInfoText').text('Live mode');
            }
        });
    }
);