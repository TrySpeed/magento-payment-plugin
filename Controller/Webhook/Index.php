<?php

namespace Tryspeed\BitcoinPayment\Controller\Webhook;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Tryspeed\BitcoinPayment\Exception\WebhookException;
use Magento\Sales\Model\Order;

class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    protected $resultJsonFactory;
    protected $webhooksLogger;
    protected $request;
    protected $response;
    protected $webhookhelper;
    protected $scopeConfig;
    protected $orderRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Response\Http $response,
        \Tryspeed\BitcoinPayment\Logger\WebhooksLogger $webhooksLogger,
        \Tryspeed\BitcoinPayment\Helper\Webhook $webhookHelper,
        ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\Order $orderRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->webhooksLogger = $webhooksLogger;
        $this->request = $request;
        $this->response = $response;
        parent::__construct($context);
        $this->webhookhelper = $webhookHelper;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Webhook execution
     *
     * @return void
     */
    public function execute()
    {
        $this->log("Start of the webhook");
        if ($this->request->getMethod() == 'GET') {
            $this->log("Your webhooks endpoint is accessible from your location");
            throw new WebhookException("Your webhooks endpoint is accessible from your location.", 200);
        } else {
            $this->log("Inside the webhook");
            $response_body = $this->request->getContent();
            $webhookId = $this->request->getHeader('Webhook-Id');
            $webhookSignature = $this->request->getHeader('Webhook-Signature');
            $webhookTimestamp = $this->request->getHeader('Webhook-Timestamp');
            $headers = [
                'webhook-signature' => $webhookSignature,
                'webhook-timestamp' => $webhookTimestamp,
                'webhook-id'  => $webhookId
            ];
            $headerData = json_encode($headers);
            $trans_mode = $this->scopeConfig->getValue('payment/speedBitcoinPayment/speed_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            if ($trans_mode == 'test') {
                $secretkey = $this->scopeConfig->getValue('payment/speedBitcoinPayment/test/speed_test_sk', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            } elseif ($trans_mode == 'live') {
                $secretkey = $this->scopeConfig->getValue('payment/speedBitcoinPayment/live/speed_live_sk', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            }
            $response = $this->webhookhelper->verify($response_body, $headers, $secretkey);
            if ($response['event_type'] == 'checkout_session.paid') {
                $this->log("IN WEBHOOK EVENT");
                $orderId = $response['data']['object']['source_id'];
                $this->log("ORDER ID : " . $orderId);
                $order = $this->orderRepository;
                $order->load($orderId);
                $orderState = Order::STATE_PROCESSING;
                $order->setState($orderState)->setStatus(Order::STATE_PROCESSING);
                $order->save();
                $this->response->setStatusCode(200);
            } else {
                $this->response->setStatusCode(500);
            }
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function log($msg)
    {
        $this->webhooksLogger->info($msg);
    }
}
