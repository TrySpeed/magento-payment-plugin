<?php

namespace Tryspeed\BitcoinPayment\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order\Config as OrderConfig;

class CancelAbandonedOrders
{
    const XML_ENABLE = 'payment/speedBitcoinPayment/enable_auto_cancel';
    const XML_CANCEL_STATUS = 'payment/speedBitcoinPayment/cancel_order_status';
    const PAYMENT_METHOD = 'speedBitcoinPayment';

    protected $orderCollectionFactory;
    protected $scopeConfig;
    protected $logger;
    protected $orderManagement;
    protected $orderRepository;
    protected $orderConfig;

    public function __construct(
        CollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        OrderConfig $orderConfig
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->orderManagement = $orderManagement;
        $this->orderRepository = $orderRepository;
        $this->orderConfig = $orderConfig;
    }

    public function execute(): void
    {

        if (!$this->scopeConfig->isSetFlag(self::XML_ENABLE)) {
            return;
        }

        $minutes = 30;

        $thresholdTime = (new \DateTime())
            ->modify("-{$minutes} minutes")
            ->format('Y-m-d H:i:s');

        $cancelStatus = $this->scopeConfig->getValue(
            self::XML_CANCEL_STATUS,
            ScopeInterface::SCOPE_STORE
        );

        $validCancelStatuses = $this->orderConfig->getStatuses();

        if (!$cancelStatus || !isset($validCancelStatuses[$cancelStatus])) {
            $this->logger->warning('Configured cancel status does not exist: ' . $cancel_status);
            $cancelStatus = Order::STATE_CANCELED;
        }

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
                    $this->logger->warning(
                        'Speed auto-cancel skipped: order cannot be canceled',
                        [
                            'order_id'     => $order->getEntityId(),
                            'increment_id' => $order->getIncrementId(),
                            'state'        => $order->getState(),
                            'status'       => $order->getStatus()
                        ]
                    );
                    continue;
                }

                $this->orderManagement->cancel($order->getEntityId());
                $order = $this->orderRepository->get($order->getEntityId());
                $order->setStatus($cancelStatus);
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
