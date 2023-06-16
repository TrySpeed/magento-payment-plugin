<?php

namespace Tryspeed\BitcoinPayment\Model\Config\Source;

class LogoDisplay implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @inheritDoc
     */

    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('Logo')],
            ['value' => 0, 'label' => __('No Logo')],
        ];
    }
}
