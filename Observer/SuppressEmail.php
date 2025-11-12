<?php

namespace Tryspeed\BitcoinPayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SuppressEmail implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();

        if ($payment && $payment->getMethod() === 'speedBitcoinPayment') {
            $order->setCanSendNewEmailFlag(false);
            $order->setSendEmail(false);
        }
    }
}
