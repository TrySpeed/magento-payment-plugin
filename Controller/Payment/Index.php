<?php

namespace Tryspeed\BitcoinPayment\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\FormKey;

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
    protected $formKey;

    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        StoreManagerInterface $storeManager,
        \Tryspeed\BitcoinPayment\Logger\WebhooksLogger $webhooksLogger,
        FormKey $formKey
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutHelper = $checkoutHelper;
        $this->checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->webhooksLogger = $webhooksLogger;
        $this->formKey = $formKey;
        parent::__construct($context);
    }
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $order = $this->checkoutSession->getLastRealOrder();
        $orderId = $order->getEntityId();
        $success_url = $this->storeManager->getStore()->getUrl('checkout/onepage/success');
        $cancel_url = $this->storeManager->getStore()->getUrl(
            'tryspeed/payment/cancel',
            [
                '_secure' => true,
                '_query' => [
                    'order_id' => (int)$orderId,
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
        $url = 'https://api.tryspeed.com/payment-page';
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
        $result = json_decode(curl_exec($curl));
        if ($result->url) {
            $response = $this->resultJsonFactory->create();
            $this->log('Redirecting to payment page');
            $response->setData(['redirect_url' => $result->url . "?source_type=magento2"]);
            return $response;
        } else {
            return $result;
        }
    }
    public function log($msg)
    {
        $this->webhooksLogger->info($msg);
    }
}
