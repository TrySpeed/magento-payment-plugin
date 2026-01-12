<?php

namespace Tryspeed\BitcoinPayment\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class CancelAbandonedOrders
{
    const XML_ENABLE = 'payment/speedBitcoinPayment/enable_auto_cancel';
    const PAYMENT_METHOD = 'speedBitcoinPayment';

    protected $orderCollectionFactory;
    protected $scopeConfig;
    protected $logger;
    protected $orderManagement;
    protected $orderRepository;

    public function __construct(
        CollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->orderManagement = $orderManagement;
        $this->orderRepository = $orderRepository;
    }

    public function execute(): void
    {

        if (!$this->scopeConfig->isSetFlag(self::XML_ENABLE, ScopeInterface::SCOPE_STORE)) {
            return;
        }

        $minutes = 30;

        $thresholdTime = (new \DateTime())
            ->modify("-{$minutes} minutes")
            ->format('Y-m-d H:i:s');

        $collection = $this->orderCollectionFactory->create();

        $collection->addFieldToFilter('state', Order::STATE_NEW)
            ->addFieldToFilter('created_at', ['lt' => $thresholdTime]);

        $collection->getSelect()->join(
            ['payment' => $collection->getTable('sales_order_payment')],
            'main_table.entity_id = payment.parent_id',
            []
        )->where(
            'payment.method = ?',
            self::PAYMENT_METHOD
        );

        foreach ($collection as $order) {
            try {
                if (!$order->canCancel()) {
                    continue;
                }

                $this->orderManagement->cancel($order->getEntityId());
                $order = $this->orderRepository->get($order->getEntityId());
                $order->addCommentToStatusHistory(
                    __('Order automatically canceled due to unpaid Speed payment.')
                );
                $this->orderRepository->save($order);
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Speed auto-cancel failed for order #' . $order->getIncrementId(),
                    ['exception' => $e]
                );
            }
        }
    }
}
