<?php

namespace Tryspeed\BitcoinPayment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;

class Cancel extends Action
{
    protected $checkoutSession;
    protected $orderFactory;
    protected $orderResource;
    protected $webhooksLogger;
    protected $logger;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        \Tryspeed\BitcoinPayment\Logger\WebhooksLogger $webhooksLogger,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderResource = $orderResource;
        $this->webhooksLogger = $webhooksLogger;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $orderId = (int)$this->getRequest()->getParam('order_id');
            if (!$orderId) {
                $this->messageManager->addErrorMessage(__('Order ID missing.'));
                return $this->_redirect('checkout/cart');
            }

            $order = $this->orderFactory->create();
            $this->orderResource->load($order, $orderId);

            if ($order && $order->getState() !== \Magento\Sales\Model\Order::STATE_CANCELED) {
                $order->cancel();
                $order->addStatusHistoryComment(__('Customer canceled the payment.'));
                $this->orderResource->save($order);
                $this->log("Order canceled by customer. Order ID: {$orderId}");
            }

            $this->checkoutSession->restoreQuote();
        } catch (\Exception $e) {
            $this->logger->error('Error canceling order', ['exception' => $e->getMessage()]);
            $this->messageManager->addErrorMessage(__('Unable to cancel the order. Please contact support.'));
        }

        return $this->_redirect('checkout/onepage/failure');
    }

    public function log($msg)
    {
        $this->webhooksLogger->info($msg);
    }
}
