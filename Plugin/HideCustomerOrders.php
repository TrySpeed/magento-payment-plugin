<?php

namespace Tryspeed\BitcoinPayment\Plugin;

use Magento\Sales\Block\Order\History;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order;

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

        $hiddenStates = [
            Order::STATE_NEW,
            Order::STATE_CANCELED
        ];

        $select = $collection->getSelect();

        $from = $select->getPart('from');
        if (!isset($from['payment'])) {
            $select->joinLeft(
                ['payment' => $collection->getTable('sales_order_payment')],
                'main_table.entity_id = payment.parent_id',
                []
            );
        }

        $connection = $collection->getConnection();

        $condition = sprintf(
            'NOT (payment.method = %s AND main_table.state IN (%s))',
            $connection->quote(self::SPEED_PAYMENT_METHOD),
            implode(',', array_map([$connection, 'quote'], $hiddenStates))
        );

        $select->where($condition);

        return $collection;
    }
}
