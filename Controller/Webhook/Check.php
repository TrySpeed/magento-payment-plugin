<?php

namespace Tryspeed\BitcoinPayment\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tryspeed\BitcoinPayment\Logger\WebhooksLogger;

class Check extends Action
{
    protected $resultJsonFactory;
    protected $scopeConfig;
    protected $storeManager;
    protected $webhooksLogger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        WebhooksLogger $webhooksLogger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig       = $scopeConfig;
        $this->storeManager      = $storeManager;
        $this->webhooksLogger    = $webhooksLogger;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $active = $this->checkWebhookStatus();

            return $result->setData([
                'success' => true,
                'active'  => $active
            ]);
        } catch (\Exception $e) {
            $this->log("EXCEPTION: " . $e->getMessage());

            return $result->setData([
                'success' => false,
                'active'  => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function checkWebhookStatus()
    {
        $trans_mode =
            $this->scopeConfig->getValue('payment/speedBitcoinPayment/speed_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($trans_mode === 'test') {
            $key   = $this->scopeConfig->getValue('payment/speedBitcoinPayment/test/speed_test_pk');
            $s_key = $this->scopeConfig->getValue('payment/speedBitcoinPayment/test/speed_test_sk');
        } else {
            $key   = $this->scopeConfig->getValue('payment/speedBitcoinPayment/live/speed_live_pk');
            $s_key = $this->scopeConfig->getValue('payment/speedBitcoinPayment/live/speed_live_sk');
        }

        $storeUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $webhook_url = $storeUrl . 'tryspeed/webhook';

        $params = [
            'url'    => $webhook_url,
            'secret' => $s_key
        ];

        $url = 'https://api.tryspeed.com/webhooks/verify-secret';
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type:application/json',
                'Authorization: Basic ' . base64_encode($key),
                'speed-version: 2022-10-15'
            ]
        );
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $result = $response ? json_decode($response) : null;

        if ($httpCode !== 200 || !$result) {
            $this->log("Webhook verify returned HTTP " . ($httpCode));
            return false;
        }

        if (isset($result->exists) && $result->exists === true) {
            if (isset($result->status) && strtolower($result->status) === 'active') {
                $this->log('Webhook verify: ACTIVE');
                return true;
            }

            $this->log('Webhook verify: exists but not ACTIVE (status=' . ($result->status ?? 'n/a') . ')');
            return false;
        }

        $this->log('Webhook verify: endpoint not found for provided URL/secret');
        return false;
    }

    private function log($msg)
    {
        $this->webhooksLogger->info($msg);
    }
}
