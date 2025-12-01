<?php

namespace Tryspeed\BitcoinPayment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class NewOrderStatusObserver implements ObserverInterface
{
    protected $scopeConfig;
    protected $orderResource;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order $orderResource
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->orderResource = $orderResource;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if ($order->getPayment()->getMethod() !== 'speedBitcoinPayment') {
            return;
        }

        $newStatus = $this->scopeConfig->getValue(
            'payment/speedBitcoinPayment/new_order_status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$newStatus) {
            return;
        }

        if ($newStatus) {
            $availableStatuses = $order->getConfig()->getStatuses();
            if (!isset($availableStatuses[$newStatus])) {
                return;
            }
        }

        if ($order->getStatus() === $newStatus) {
            return;
        }

        $order->setStatus($newStatus);

        $this->orderResource->save($order);
    }
}
