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

        $collection->addFieldToFilter(
            'state',
            ['nin' => [
                Order::STATE_NEW,
                Order::STATE_CANCELED
            ]]
        );

        $select = $collection->getSelect();
        $from   = $select->getPart(\Zend_Db_Select::FROM);

        if (!isset($from['payment'])) {
            $select->join(
                ['payment' => $collection->getTable('sales_order_payment')],
                'main_table.entity_id = payment.parent_id',
                []
            );
        }

        $select->where(
            'payment.method = ?',
            self::SPEED_PAYMENT_METHOD
        );

        return $collection;
    }
}
