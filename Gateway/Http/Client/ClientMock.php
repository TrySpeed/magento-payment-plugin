<?php

namespace Tryspeed\BitcoinPayment\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

class ClientMock implements \Magento\Payment\Gateway\Http\ClientInterface
{
    public const SUCCESS = 1;
    public const FAILURE = 0;

    /**
     * @var array
     */
    private $results = [
        self::SUCCESS,
        self::FAILURE
    ];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ClientMock constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = $this->generateResponseForCode(
            $this->getResultCode(
                $transferObject
            )
        );

        $this->logger->debug(
            [
                'request' => $transferObject->getBody(),
                'response' => $response
            ]
        );

        return $response;
    }

    /**
     * GenerateResponseForCode
     *
     * @param ResultCode $resultCode
     * @return array
     */
    protected function generateResponseForCode($resultCode)
    {

        return array_merge(
            [
                'RESULT_CODE' => $resultCode,
                'TXN_ID' => $this->generateTxnId()
            ],
            $this->getFieldsBasedOnResponseType($resultCode)
        );
    }

    /**
     * GenerateTxnId
     * 
     * @return string
     */
    protected function generateTxnId()
    {
        return hash('sha256', random_int(0, 1000));
    }

    /**
     * GetResultCode
     *
     * @param  TransferInterface $transfer
     * @return int|mixed
     */
    private function getResultCode(TransferInterface $transfer)
    {
        $headers = $transfer->getHeaders();

        if (isset($headers['force_result'])) {
            return (int)$headers['force_result'];
        }

        return $this->results[random_int(0, 1)];
    }

    /**
     * GetFieldsBasedOnResponseType
     *
     * @return array
     */
    private function getFieldsBasedOnResponseType($resultCode)
    {
        switch ($resultCode) {
            case self::FAILURE:
                return [
                    'FRAUD_MSG_LIST' => [
                        'Stolen card',
                        'Customer location differs'
                    ]
                ];
        }

        return [];
    }
}
