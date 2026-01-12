<?php

namespace Tryspeed\BitcoinPayment\Plugin;

use Magento\Sales\Block\Order\History;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class HideCustomerOrders
{
    const XML_PATH_HIDE_ORDERS = 'payment/speedBitcoinPayment/hide_pending_orders';
    const SPEED_PAYMENT_METHOD = 'speedBitcoinPayment';

    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function afterGetOrders(History $subject, $collection)
    {
        if (!$collection) {
            return $collection;
        }

        if (!$this->scopeConfig->isSetFlag(
            self::XML_PATH_HIDE_ORDERS,
            ScopeInterface::SCOPE_STORE
        )) {
            return $collection;
        }

        $select = $collection->getSelect();

        $from = $select->getPart('from');
        if (!isset($from['payment'])) {
            $select->joinLeft(
                ['payment' => $collection->getTable('sales_order_payment')],
                'main_table.entity_id = payment.parent_id',
                []
            );
        }

        $select->where('payment.method != ?', self::SPEED_PAYMENT_METHOD);
        $select->orWhere(
            'main_table.state NOT IN (?)',
            [Order::STATE_NEW, Order::STATE_CANCELED]
        );

        return $collection;
    }
}
