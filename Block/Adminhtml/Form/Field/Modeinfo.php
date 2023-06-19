<?php

namespace Tryspeed\BitcoinPayment\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Backend\Block\Template\Context;

class Modeinfo extends Field
{
    private $paymentsHelper;
    protected $storeManager;
    protected $assetRepository;

    public function __construct(
        Context $context,
        \Tryspeed\BitcoinPayment\Helper\Generic $paymentsHelper,
        StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepository
    ) {
         parent::__construct($context);
        $this->paymentsHelper = $paymentsHelper;
        $this->storeManager = $storeManager;
        $this->assetRepository = $assetRepository;
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
        $storeName = $this->storeManager->getStore()->getName();
        $logoImg =  $this->getViewFileUrl('Tryspeed_BitcoinPayment::images/Logo.png');
        
        $html = <<<TEXT
            <span type="text" id="modeInfo" class="input-text admin__control-text transModeInfo" style="color: #000;background-color: #f9df00;
            display: flex;align-items: center;justify-content: flex-start;
            position: relative;padding: 3px 14px;margin-bottom: 1rem;font-weight: bold;border:0;border-bottom-left-radius: 5px;padding: 3px 14px;">
                <span id="modeInfoText">Live mode</span>
            </span>
            <input type="hidden" id="store_name" title="Store Name" value="$storeName">
            <input type="hidden" id="logo_img" title="Logo Image" value="$logoImg">
            TEXT;
        // @codingStandardsIgnoreEnd

        return $html;
    }
}
