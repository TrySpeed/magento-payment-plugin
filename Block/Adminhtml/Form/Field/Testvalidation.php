<?php

namespace Tryspeed\BitcoinPayment\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;

class Testvalidation extends Field
{
    /**
     * @inheritDoc
     */
    protected function _renderScopeLabel(AbstractElement $element): string
    {
        // Return empty label
        return '';
    }
    /**
     * @inheritDoc
     * 
     * @throws LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        // Replace field markup with validation button
        $title = __('Test Connection');
        $html = <<<TEXT
            <button
                type="button"
                title="{$title}"
                class="button"
                onclick="speedTestApiValidator()">
                <span>{$title}</span>
            </button>
        TEXT;
        // @codingStandardsIgnoreEnd

        return $html;
    }
}
