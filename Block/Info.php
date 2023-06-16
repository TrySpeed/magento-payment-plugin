<?php

namespace Tryspeed\BitcoinPayment\Block;

use Magento\Framework\Phrase;

class Info extends \Magento\Payment\Block\ConfigurableInfo
{
    /**
     * GetLabel
     *
     * @param  string $field
     * @return Phrase|string
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * GetValueView
     *
     * @param  string $field
     * @param  string $value
     * @return Phrase|string
     */
    protected function getValueView($field, $value)
    {
        return parent::getValueView($field, $value);
    }
}
