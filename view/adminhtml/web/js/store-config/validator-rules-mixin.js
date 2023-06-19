define(
    ['jquery'], function ($) {
        'use strict'
        return function (target) {
            $.validator.addMethod(
                'validate-paymentMethod',
                function (value) {
                    if (value != '') {
                        return true;
                    } else {
                        return false;
                    }
                },
                $.mage.__('Enter Valid Payment Method Name.')
            );
            $.validator.addMethod(
                'validate-paymentMethodDesc',
                function (value) {
                    if (value != '') {
                        return true;
                    } else {
                        return false;
                    }
                },
                $.mage.__('Enter Payment Method Descriprtion.')
            );
            $.validator.addMethod(
                'validate-methodSeq',
                function (value) {
                    if (value != '') {
                        return true;
                    } else {
                        return false;
                    }
                },
                $.mage.__('Enter Payment Method Sequence.')
            );
            $.validator.addMethod(
                'validate-statementDesc',
                function (value) {
                    if (value != '') {
                        return true;
                    } else {
                        return false;
                    }
                },
                $.mage.__('Enter Statement Descriptor.')
            );
            $.validator.addMethod(
                'validate-testPk',
                function (value) {
                    const authToken = btoa(value);
                    var result = false;
                    var errorMessage = '';
                    if(value != ''){
                        if(/^pk_test_/.test(value)){
                            $.ajax(
                                {
                                    type: "POST",
                                    url: "https://api.tryspeed.com/webhooks/verify-secret",
                                    headers: { authorization: "Basic " + authToken, 'speed-version':'2022-10-15'},
                                    data: JSON.stringify({}),
                                    contentType: "application/json",
                                    dataType: "text",
                                    async: false,
                                    success: function (response) {
                                        result = true;
                                    },
                                    error: function (textStatus, errorThrown) {
                                        if(textStatus.status != 403){
                                            result = true;
                                        }else{
                                            errorMessage = 'Please ensure that you have entered a valid Test Publishable Key.'
                                        }
                                    }
                                }
                            )
                        }else{
                            errorMessage = 'Please ensure that you have entered a valid Test Publishable Key.'
                        }
                        $.validator.messages['validate-testPk'] = $.mage.__(errorMessage);
                    }
                    return result;
                },
                $.mage.__('Enter Test publishable key.')
            );
            $.validator.addMethod(
                'validate-livePk',
                function (value) {
                    const authToken = btoa(value);
                    var result = false;
                    var errorMessage = '';
                    if(value != ''){
                        if(/^pk_live_/.test(value)){
                            $.ajax(
                            {
                                type: "POST",
                                url: "https://api.tryspeed.com/webhooks/verify-secret",
                                headers: { authorization: "Basic " + authToken, 'speed-version': '2022-10-15' },
                                data: JSON.stringify({}),
                                contentType: "application/json",
                                dataType: "text",
                                async: false,
                                success: function (response) {
                                    result = true;
                                },
                                error: function (textStatus, errorThrown) {
                                    if(textStatus.status != 403){
                                        result = true;
                                    }else{
                                        errorMessage = 'Please ensure that you have entered a valid Live Publishable Key.'
                                    }
                                }
                            }
                        )
                        }else{
                            errorMessage = 'Please ensure that you have entered a valid Live Publishable Key.'
                        }
                        $.validator.messages['validate-livePk'] = $.mage.__(errorMessage);
                    }
                    return result;
                },
                $.mage.__('Enter Live publishable key.')
            );
            $.validator.addMethod(
                'validate-testSsk',
                function (value) {
                    const apiKey = $('#payment_us_speedBitcoinPayment_test_speed_test_pk').val();
                    const authToken = btoa(apiKey);
                    var result = false;
                    var errorMessage = '';
                    if(value != ''){
                        if(/^pk_test_/.test(apiKey)){
                            $.ajax(
                                {
                                    type: "POST",
                                    url: "https://api.tryspeed.com/webhooks/verify-secret",
                                    headers: { authorization: "Basic " + authToken, 'speed-version': '2022-10-15' },
                                    data: JSON.stringify(
                                        {
                                            url: $("#speed_test_webhook_url").val(),
                                            secret: value,
                                        }
                                    ),
                                    contentType: "application/json",
                                    dataType: "text",
                                    async: false,
                                    success: function (response) {
                                        let successRes = JSON.parse(response);
                                        if(successRes.exists && successRes.status == 'active'){
                                           result = true;
                                        }else{
                                            result = false;
                                            errorMessage = 'To mark an order as paid using a webhook, the speed webhook endpoint URL needs to be activated'
                                        }
                                    },
                                    error: function (textStatus, errorThrown) {
                                        let erroeRes = JSON.parse(textStatus.responseText);
                                        if(textStatus.status == 403){
                                            result = true;
                                        }else{
                                            result = false;
                                            errorMessage = erroeRes.errors[0].message
                                        }
                                    }
                                }
                            )
                        }else{
                            result = true;
                        }
                        $.validator.messages['validate-testSsk'] = $.mage.__(errorMessage);
                    }
                    return result;
                },
                $.mage.__('Enter Webhook Test Signing Secret Key')
            );
            $.validator.addMethod(
                'validate-liveSsk',
                function (value) {
                    const apiKey = $('#payment_us_speedBitcoinPayment_live_speed_live_pk').val();
                    const authToken = btoa(apiKey);
                    var result = false;
                    var errorMessage = '';
                    if(value != ''){
                        if(/^pk_live_/.test(apiKey)){
                            $.ajax(
                                {
                                    type: "POST",
                                    url: "https://api.tryspeed.com/webhooks/verify-secret",
                                    headers: { authorization: "Basic " + authToken, 'speed-version': '2022-10-15'},
                                    data: JSON.stringify(
                                        {
                                            url: $("#speed_live_webhook_url").val(),
                                            secret: value,
                                        }
                                    ),
                                    contentType: "application/json",
                                    dataType: "text",
                                    async: false,
                                    success: function (response) {
                                        let successRes = JSON.parse(response);
                                        if(successRes.exists && successRes.status == 'active'){
                                           result = true; 
                                        }else{
                                            result = false;
                                            errorMessage = 'To mark an order as paid using a webhook, the speed webhook endpoint URL needs to be activated'
                                        }
                                    },
                                    error: function (textStatus, errorThrown) {
                                        let erroeRes = JSON.parse(textStatus.responseText);
                                        if(textStatus.status == 403){
                                            result = true;
                                        }else{
                                            result = false;
                                            errorMessage = erroeRes.errors[0].message
                                        }
                                    }
                                }
                            )
                        }else{
                            result = true;
                        }
                        $.validator.messages['validate-liveSsk'] = $.mage.__(errorMessage);
                    }
                    return result;
                },
                $.mage.__('Enter Valid Webhook Live Signing Secret Key')
            );
            return target;
        };
    }
);