<?php

namespace Tryspeed\BitcoinPayment\Model\Ui;

use Tryspeed\BitcoinPayment\Gateway\Http\Client\ClientMock;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * Payment Gateway Code
     */
    const CODE = 'speedBitcoinPayment';
    protected $scopeConfig;
    protected $assetRepo;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Asset\Repository $assetRepo
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->assetRepo = $assetRepo;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $description = $this->scopeConfig->getValue('payment/speedBitcoinPayment/description', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $logDisp = $this->scopeConfig->getValue('payment/speedBitcoinPayment/logsec', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $logoImg = $this->assetRepo->getUrl('Tryspeed_BitcoinPayment::img/Logo.png');
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ],
                    'description' => $description,
                    'logoDisplay' => $logDisp,
                    'logoImage' => $logoImg
                ]
            ]
        ];
    }
}
