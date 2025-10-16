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
use Psr\Log\LoggerInterface;
use Tryspeed\BitcoinPayment\Helper\Webhook as WebhookHelper;
use Tryspeed\BitcoinPayment\Logger\WebhooksLogger;

class Index extends Action implements CsrfAwareActionInterface
{
    protected $scopeConfig;
    protected $orderRepository;
    protected $webhookHelper;
    protected $request;
    protected $response;
    protected $webhooksLogger;
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;
    protected $logger;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Response\Http $response,
        WebhooksLogger $webhooksLogger,
        WebhookHelper $webhookHelper,
        Order $orderRepository,
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->response = $response;
        $this->webhooksLogger = $webhooksLogger;
        $this->webhookHelper = $webhookHelper;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
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
                $this->log('Webhook accessed with non-POST method');
                $this->response->setStatusCode(405); // Method Not Allowed
                return;
            }

            $payload = $this->request->getContent();
            $data = json_decode($payload, true);

            if (!$data) {
                $this->log('Invalid JSON payload received');
                $this->response->setStatusCode(400); // Bad Request
                return;
            }

            $eventType = $data['event_type'] ?? null;
            $checkoutData = $data['data']['object'] ?? null;

            if (!$eventType || !$checkoutData) {
                $this->log('Webhook missing event_type or data.object');
                $this->response->setStatusCode(400);
                return;
            }

            $this->log("Received event: {$eventType}");

            // Only handle successful checkout
            if ($eventType !== 'checkout_session.paid') {
                $this->log("Skipping event type: {$eventType}");
                $this->response->setStatusCode(204);
                return;
            }

            $orderId = $checkoutData['source_id'] ?? null;
            if (!$orderId) {
                $this->log('No Magento order ID in webhook payload');
                $this->response->setStatusCode(400);
                return;
            }

            $order = $this->orderRepository->load($orderId);
            if (!$order || !$order->getId()) {
                $this->log("Order not found: {$orderId}");
                $this->response->setStatusCode(404);
                return;
            }

            $this->log("Processing order ID: {$orderId}");

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
                $this->log("Payment information saved for order ID: {$orderId}");
            }

            // Automatically create invoice
            if ($order->canInvoice()) {
                $this->log("Order ID {$orderId} can be invoiced: true");
                $invoice = $this->invoiceService->prepareInvoice($order);

                if ($invoice && $invoice->getTotalQty()) {
                    $invoice->register();
                    $invoice->getOrder()->setIsInProcess(true);

                    $transaction = $this->transaction
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                    $transaction->save();

                    $this->invoiceSender->send($invoice);
                    $this->log("Invoice automatically created for order ID: {$orderId}");
                } else {
                    $this->log("Invoice not created: order has no items or zero quantity");
                }
            } else {
                $this->log("Order ID {$orderId} cannot be invoiced at this time");
            }

            $this->response->setStatusCode(200);
        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed: ' . $e->getMessage());
            $this->log('Webhook processing failed: ' . $e->getMessage());
            $this->response->setStatusCode(500);
        }
    }

    /**
     * Log message
     */
    protected function log($message)
    {
        if ($this->webhooksLogger) {
            $this->webhooksLogger->info($message);
        }
    }
}
