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
    protected $_isInitializeNeeded = false;
    protected $_canUseForMultishipping = false;
}
