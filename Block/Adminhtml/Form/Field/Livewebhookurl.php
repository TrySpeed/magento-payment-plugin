<?php

namespace Tryspeed\BitcoinPayment\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;

class Livewebhookurl extends Field
{
    private $paymentsHelper;
    protected $storeManager;

    public function __construct(
        Tryspeed\BitcoinPayment\Helper\Generic $paymentsHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->paymentsHelper = $paymentsHelper;
        $this->storeManager = $storeManager;
    }
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
        $storeUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $webhook_url = $storeUrl . 'tryspeed/webhook';
        $webhook_url = preg_replace("/^http:/i", "https:", $webhook_url);
        // Replace field markup with validation button
        $title = __('Copy URL');
        $html = <<<TEXT
            <input
                type="text"
                title="webhook url" id="speed_live_webhook_url"
                class="input-text admin__control-text" value="$webhook_url" readonly>
                <button
                    type="button"
                    title="{$title}"
                    class="button"
                    onclick="copyLiveSpeedWebhookUrl()">
                    <span>{$title}</span>
                </button>
                <div class="webhook-live-url-success d-none">Copied!</div>
            TEXT;
        // @codingStandardsIgnoreEnd
        return $html;
    }
}
