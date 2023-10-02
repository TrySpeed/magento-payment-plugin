<?php

namespace Tryspeed\BitcoinPayment\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Tryspeed\BitcoinPayment\Exception\WebhookException;

class Webhook
{
    protected $output = null;
    public const SECRET_PREFIX = "wsec_";
    private $secret;
    protected $request;
    protected $response;
    protected $webhooksLogger;
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Response\Http $response,
        ScopeConfigInterface $scopeConfig,
        \Tryspeed\BitcoinPayment\Logger\WebhooksLogger $webhooksLogger
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->webhooksLogger = $webhooksLogger;
        $this->scopeConfig = $scopeConfig;
    }

    public function setOutput(\Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->output = $output;
    }

    public function verify($payload, $headers, $secret)
    {
        if (substr($secret, 0, strlen(Webhook::SECRET_PREFIX)) === Webhook::SECRET_PREFIX) {
            $secret = substr($secret, strlen(Webhook::SECRET_PREFIX));
        }
        $this->secret = base64_decode($secret);
        if (isset($headers['webhook-id']) && isset($headers['webhook-timestamp']) && isset($headers['webhook-signature'])) {
            $msgId = $headers['webhook-id'];
            $msgTimestamp = $headers['webhook-timestamp'];
            $msgSignature = $headers['webhook-signature'];
        } else {
            $this->log("WEBHOOK Headers Not Sent");
            throw new WebhookException("Missing required headers");
        }

        $signature = $this->sign($msgId, $msgTimestamp, $payload);
        $expectedSignature = explode(',', $signature, 2)[1];
        $passedSignatures = explode(',', $msgSignature, 2);

        $version = $passedSignatures[0];
        $passedSignature = $passedSignatures[1];

        if (strcmp($version, "v1") == 0) {
            if (hash_equals($expectedSignature, $passedSignature)) {
                return json_decode($payload, true);
            } else {
                $this->log("Webhook Signature not matching");
                throw new WebhookException("No matching signature found");
            }
        } else {
            $this->log("Version does not match");
            throw new WebhookException("No matching version found");
        }
    }

    public function sign($msgId, $timestamp, $payload)
    {
        $is_positive_integer = ctype_digit($timestamp);
        if (!$is_positive_integer) {
            $this->log("WEBHOOK TimeStamp is not positive integer");
            throw new WebhookException("Invalid timestamp");
        }
        $toSign = "{$msgId}.{$timestamp}.{$payload}";
        $hex_hash = hash_hmac('sha256', $toSign, $this->secret);
        $signature = base64_encode(pack('H*', $hex_hash));
        return "v1,{$signature}";
    }

    public function log($msg)
    {
        $this->webhooksLogger->info($msg);
    }
}
