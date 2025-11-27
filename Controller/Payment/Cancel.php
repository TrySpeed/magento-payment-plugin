<?php

namespace Tryspeed\BitcoinPayment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Cancel extends Action
{
    protected $checkoutSession;
    protected $orderFactory;
    protected $orderResource;
    protected $logger;
    protected $scopeConfig;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        \Tryspeed\BitcoinPayment\Logger\WebhooksLogger $logger,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderResource = $orderResource;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
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
            $cancel_status = $this->scopeConfig->getValue('payment/speedBitcoinPayment/cancel_order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            if ($order && $order->getState() !== \Magento\Sales\Model\Order::STATE_CANCELED) {
                $order->cancel();
                if ($cancel_status) {
                    $availableStatuses = $order->getConfig()->getStatuses();
                    if (!isset($availableStatuses[$cancel_status])) {
                        $this->logger->warning('Configured cancel status does not exist: ' . $cancel_status);
                        $cancel_status = null; // fallback
                    }
                    $order->setStatus($cancel_status);
                }
                $this->orderResource->save($order);
            }

            $this->checkoutSession->restoreQuote();
        } catch (\Exception $e) {
            $this->logger->error('Error canceling order', ['exception' => $e->getMessage()]);
            $this->messageManager->addErrorMessage(__('Unable to cancel the order. Please contact support.'));
        }

        return $this->_redirect('checkout/cart');
    }
}
