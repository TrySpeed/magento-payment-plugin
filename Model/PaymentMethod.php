<?php

namespace Tryspeed\BitcoinPayment\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\Validator\Exception;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Framework\Exception\CouldNotSaveException;

class PaymentMethod extends \Magento\Payment\Model\Method\Adapter
{
    protected $_code = "speedBitcoinPayment";
    protected $_canUseCheckout = true;
    protected $_canUseInternal = true;
    protected $_isInitializeNeeded = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;

    public function initialize($paymentAction, $stateObject)
    {
        $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;

        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
        return $this;
    }
}
