<?php

namespace Tryspeed\BitcoinPayment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

class Cancel extends Action
{
    protected $checkoutSession;
    protected $orderFactory;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
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
            }

            $this->checkoutSession->restoreQuote();
            $this->messageManager->addNoticeMessage(__('You have canceled the payment.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to cancel the order: %1', $e->getMessage()));
        }

        return $this->_redirect('checkout/onepage/failure');
    }
}
