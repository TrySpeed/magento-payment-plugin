# Magento 2 Speed Plugin

## Installation via Composer

You can install Magento 2 Speed plugin via [Composer](http://getcomposer.org/). Run the following command in your terminal:

1. Go to your Magento 2 root folder.

2. Enter following commands to install plugin:

    ```bash
    composer require tryspeed/magento-payment-plugin
    ```
   Wait while dependencies are updated.

3. Enter following commands to enable plugin:

    ```bash
    php bin/magento module:enable Tryspeed_BitcoinPayment --clear-static-content
    php bin/magento setup:upgrade
    ```

## Plugin Configuration

Enable and configure Speed plugin in Magento Admin under `Stores / Configuration / Sales / Payment Methods / Speed Bitcoin Payment`.
