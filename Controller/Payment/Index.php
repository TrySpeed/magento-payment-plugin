<?php

namespace Tryspeed\BitcoinPayment\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $resultPageFactory;
    protected $scopeConfig;
    protected $storeManager;
    protected $checkoutHelper;
    protected $checkoutSession;
    protected $quoteFactory;
    protected $webhooksLogger;

    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        StoreManagerInterface $storeManager,
        \Tryspeed\BitcoinPayment\Logger\WebhooksLogger $webhooksLogger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutHelper = $checkoutHelper;
        $this->checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->webhooksLogger = $webhooksLogger;
        parent::__construct($context);
    }
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $order = $this->checkoutSession->getLastRealOrder();
        $response = $this->resultJsonFactory->create();

        if (!$order || !$order->getEntityId()) {
            $this->webhooksLogger->error('No valid order found in session');
            return $response->setData(['error' => true, 'message' => 'Unable to initiate payment.']);
        }
        $orderId = (int)$order->getEntityId();
        $protectCode = $order->getProtectCode();

        $success_url = $this->storeManager->getStore()->getUrl('checkout/onepage/success');
        $cancel_url = $this->storeManager->getStore()->getUrl(
            'tryspeed/payment/cancel',
            [
                '_secure' => true,
                '_query' => [
                    'order_id' => $orderId,
                    'protected_code' => $protectCode
                ]
            ]
        );
        $trans_mode = $this->scopeConfig->getValue('payment/speedBitcoinPayment/speed_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($trans_mode == 'test') {
            $key = $this->scopeConfig->getValue('payment/speedBitcoinPayment/test/speed_test_pk', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        } elseif ($trans_mode == 'live') {
            $key = $this->scopeConfig->getValue('payment/speedBitcoinPayment/live/speed_live_pk', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        $statement_descriptor = $this->scopeConfig->getValue('payment/speedBitcoinPayment/descriptor', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($statement_descriptor == '') {
            $statement_descriptor = 'Payment to Speed Magento2';
        }

        $payment_description = $this->scopeConfig->getValue('payment/speedBitcoinPayment/payment_description', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $params = [
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'statement_descriptor' => $statement_descriptor,
            'description' => 'Magento2 Store Order Id - ' . $orderId,
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'source' => "Speed Magento2",
            'source_id' => $orderId
        ];

        if ($payment_description != '') {
            $storeName = $order->getStore()->getName();
            if ($storeName == '') {
                $storeName = 'Magento Store';
            }

            $params['title'] = $storeName;
            $params['title_description'] = $payment_description;
        }

        $url = 'https://api.tryspeed.com/payment-page';
        $encodedData = json_encode($params);
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_POST              => true,
            CURLOPT_POSTFIELDS        => $encodedData,
            CURLOPT_HTTPHEADER        => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($key),
                'speed-version: 2022-10-15'
            ],
            CURLOPT_SSL_VERIFYPEER    => true,
            CURLOPT_SSL_VERIFYHOST    => 2,
            CURLOPT_TIMEOUT           => 10,
            CURLOPT_CONNECTTIMEOUT    => 5,
            CURLOPT_FOLLOWLOCATION    => false,
        ]);

        $curlResponse = curl_exec($curl);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError || !$curlResponse) {
            $this->webhooksLogger->error('Speed API cURL Error', ['error' => $curlError]);
            return $response->setData(['error' => true, 'message' => 'Unable to initiate payment.']);
        }

        $result = json_decode($curlResponse);

        if ($result->url) {
            $response->setData(['redirect_url' => $result->url . "?source_type=magento2"]);
            return $response;
        } else {
            return $result;
        }
    }
}
