<?php

namespace Tryspeed\BitcoinPayment\Controller\Payment;

class Cancel extends \Magento\Framework\App\Action\Action
{
    protected $checkoutHelper;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Helper\Data $checkoutHelper,
    ) {
        parent::__construct($context);

        $this->checkoutHelper = $checkoutHelper;
    }

    /**
     * Payment cancel function
     *
     * @return void
     */
    public function execute()
    {
        $session = $this->checkoutHelper->getCheckout();
        $lastRealOrderId = $session->getLastRealOrderId();
        $session->restoreQuote();
        $session->setLastRealOrderId($lastRealOrderId);
        return $this->_redirect('checkout/cart');
    }
}
