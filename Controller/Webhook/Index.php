<?php

namespace Tryspeed\BitcoinPayment\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Tryspeed\BitcoinPayment\Helper\Webhook as WebhookHelper;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Index extends Action implements CsrfAwareActionInterface
{
    protected $scopeConfig;
    protected $orderRepository;
    protected $webhookHelper;
    protected $request;
    protected $response;
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;
    protected $productMetadata;
    protected $orderSender;
    protected $logger;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Response\Http $response,
        WebhookHelper $webhookHelper,
        Order $orderRepository,
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        ProductMetadataInterface $productMetadata,
        OrderSender $orderSender,
        \Tryspeed\BitcoinPayment\Logger\WebhooksLogger $logger
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->response = $response;
        $this->webhookHelper = $webhookHelper;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->productMetadata = $productMetadata;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
    }

    /**
     * CSRF validation exception
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Main webhook execution
     */
    public function execute()
    {
        try {
            if ($this->request->getMethod() !== 'POST') {
                $this->response->setStatusCode(405); // Method Not Allowed
                return;
            }

            $magentoVersion = $this->productMetadata->getVersion();
            $this->logger->info("Magento Version : " . $magentoVersion);

            $headers = [
                'webhook-id' => $this->request->getHeader('Webhook-Id'),
                'webhook-timestamp' => $this->request->getHeader('Webhook-Timestamp'),
                'webhook-signature' => $this->request->getHeader('Webhook-Signature')
            ];

            $trans_mode = $this->scopeConfig->getValue('payment/speedBitcoinPayment/speed_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            if ($trans_mode == 'test') {
                $secretkey = $this->scopeConfig->getValue('payment/speedBitcoinPayment/test/speed_test_sk', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            } elseif ($trans_mode == 'live') {
                $secretkey = $this->scopeConfig->getValue('payment/speedBitcoinPayment/live/speed_live_sk', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            }
            $payload = $this->request->getContent();

            $data = $this->webhookHelper->verify($payload, $headers, $secretkey);

            if (!$data) {
                $this->logger->error('Invalid JSON payload received');
                $this->response->setStatusCode(400); // Bad Request
                return;
            }

            $eventType = $data['event_type'] ?? null;
            $checkoutData = $data['data']['object'] ?? null;

            if (!$eventType || !$checkoutData) {
                $this->response->setStatusCode(400);
                return;
            }

            // Only handle successful checkout
            if ($eventType !== 'checkout_session.paid') {
                $this->response->setStatusCode(204);
                return;
            }

            $orderId = $checkoutData['source_id'] ?? null;
            if (!$orderId) {
                $this->response->setStatusCode(400);
                return;
            }

            $order = $this->orderRepository->load($orderId);
            if (!$order || !$order->getId()) {
                $this->response->setStatusCode(404);
                return;
            }

            $this->logger->info("Processing order ID: {$orderId}");

            // Save payment info
            $payment = $order->getPayment();
            if ($payment) {
                $paymentInfo = $checkoutData['payments'][0] ?? [];

                $payment->setAdditionalInformation('checkout_session_id', $checkoutData['id'] ?? null);
                $payment->setAdditionalInformation('amount', $checkoutData['amount'] ?? null);
                $payment->setAdditionalInformation('currency', $checkoutData['currency'] ?? null);
                $payment->setAdditionalInformation('amount_paid', $checkoutData['amount_paid'] ?? null);

                if (!empty($paymentInfo)) {
                    $payment->setAdditionalInformation('payment_id', $paymentInfo['id'] ?? null);
                    $payment->setAdditionalInformation('target_amount_paid', $paymentInfo['target_amount_paid'] ?? null);
                    $payment->setAdditionalInformation('target_currency', $paymentInfo['target_currency'] ?? null);
                    $payment->setAdditionalInformation('exchange_rate', $paymentInfo['exchange_rate'] ?? null);
                }

                $payment->save();
                if (!$order->getEmailSent()) {
                    $order->setSendEmail(true);
                    $order->setCanSendNewEmailFlag(true);
                    $this->orderSender->send($order);
                    $order->addStatusHistoryComment(__('Order paid, confirmation email sent.'));
                    $this->orderRepository->save($order);
                }
            }

            // Automatically create invoice
            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);

                if ($invoice && $invoice->getTotalQty()) {
                    $invoice->register();
                    $invoice->getOrder()->setIsInProcess(true);

                    $transaction = $this->transaction
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                    $transaction->save();

                    $this->invoiceSender->send($invoice);
                } else {
                    $this->logger->error("Invoice not created: order has no items or zero quantity");
                }
            } else {
                $this->logger->error("Order ID {$orderId} cannot be invoiced at this time");
            }

            $this->response->setStatusCode(200);
        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed: ' . $e->getMessage());
            $this->response->setStatusCode(500);
        }
    }
}
