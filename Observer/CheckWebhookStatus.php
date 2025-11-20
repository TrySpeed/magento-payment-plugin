<?php

namespace Tryspeed\BitcoinPayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Payment\Model\MethodInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class CheckWebhookStatus extends AbstractDataAssignObserver
{
    protected $scopeConfig;
    protected $storeManager;
    protected $webhooksLogger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        \Tryspeed\BitcoinPayment\Logger\WebhooksLogger $webhooksLogger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->webhooksLogger = $webhooksLogger;
    }

    /**
     * 
     * @return void
     */
    public function execute(Observer $observer)
    {
        $methodInstance = $observer->getData('method_instance');
        $result = $observer->getData('result');

        $webhookIsActive = $this->checkWebhookStatus();

        if (!$webhookIsActive && $methodInstance->getCode() == 'speedBitcoinPayment') {
            $result->setData('is_available', false);
        }
    }

    public function checkWebhookStatus()
    {
        $module_status = $this->scopeConfig->getValue('payment/speedBitcoinPayment/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($module_status == 0) {
            $this->log('Speed bitcoin payment plugin is disabled.');
            return false;
        }
        $trans_mode = $this->scopeConfig->getValue('payment/speedBitcoinPayment/speed_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($trans_mode == 'test') {
            $key = $this->scopeConfig->getValue('payment/speedBitcoinPayment/test/speed_test_pk', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $s_key = $this->scopeConfig->getValue('payment/speedBitcoinPayment/test/speed_test_sk', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        } elseif ($trans_mode == 'live') {
            $key = $this->scopeConfig->getValue('payment/speedBitcoinPayment/live/speed_live_pk', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $s_key = $this->scopeConfig->getValue('payment/speedBitcoinPayment/live/speed_live_sk', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        $storeUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $webhook_url = $storeUrl . 'tryspeed/webhook';
        $webhook_url = preg_replace("/^http:/i", "https:", $webhook_url);

        $params = [
            'url' => $webhook_url,
            'secret' => $s_key
        ];
        $url = 'https://api.tryspeed.com/webhooks/verify-secret';
        $encodedData = json_encode($params);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedData);
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
            $this->log('Webhook verify returned HTTP ' . ($httpCode ?? 'n/a'));
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

    public function log($msg)
    {
        $this->webhooksLogger->info($msg);
    }
}
