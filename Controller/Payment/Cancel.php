<?php

namespace Tryspeed\BitcoinPayment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;

class Cancel extends Action
{
    protected $checkoutSession;
    protected $orderFactory;
    protected $webhooksLogger;
    protected $logger;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        \Tryspeed\BitcoinPayment\Logger\WebhooksLogger $webhooksLogger,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->webhooksLogger = $webhooksLogger;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();
            if ($order && $order->getId() && $order->getState() !== \Magento\Sales\Model\Order::STATE_CANCELED) {
                $order->cancel();
                $order->addStatusHistoryComment(__('Customer canceled the payment.'));
                $order->save();
                $this->log("Order canceled by customer Order Id - " . $order->getId());
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
