<?php

namespace Tryspeed\BitcoinPayment\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Backend\Model\Session;
use Psr\Log\LoggerInterface;
use Magento\Framework\Validator\Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Store\Model\ScopeInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\LocalizedException;

class Generic
{
    public $urlBuilder = null;
    private $orderFactory;

    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->orderFactory = $orderFactory;
    }

    public function getUrl($path, $additionalParams = [])
    {
        return $this->urlBuilder->getUrl($path, $additionalParams);
    }

    public function loadOrderByIncrementId($incrementId, $useCache = true)
    {
        if (!isset($this->orders)) {
            $this->orders = [];
        }

        if (empty($incrementId)) {
            return null;
        }

        if (!empty($this->orders[$incrementId]) && $useCache) {
            return $this->orders[$incrementId];
        }

        try {
            $orderModel = $this->orderFactory->create();
            $order = $orderModel->loadByIncrementId($incrementId);
            if ($order && $order->getId()) {
                return $this->orders[$incrementId] = $order;
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}
