<?php

namespace Tryspeed\BitcoinPayment\Model\Adminhtml\Source;

use Magento\Payment\Model\MethodInterface;

class PaymentAction implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => MethodInterface::ACTION_AUTHORIZE,
                'label' => __('Authorize')
            ]
        ];
    }
}
