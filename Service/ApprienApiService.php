<?php

namespace PHZ\ApprienMagento\Service;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class ApprienApiService {
    const API_URL = 'https://api.apprien.com/api/1/';
    private Client $client;
    private LoggerInterface $logger;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->client = new Client([
            "base_uri" => self::API_URL
        ]);
        $this->scopeConfig = $scopeConfig;
    }

    public function patchDeal($locationId, $productId, $resourceId, $deal, $providerId) {
        $clientId = $this->scopeConfig->getValue("apprien_pricing/authentication/clientId");
        $clientSecret = $this->scopeConfig->getValue("apprien_pricing/authentication/clientSecret");
        $authString = "{$clientId}:{$clientSecret}";
        $auth = base64_encode($authString);
        $this->logger->info("AUTH", ["base64" => $auth, "auth" => $authString]);
        try {
            $response = $this->client->request(
                "PATCH",
                "locations/${locationId}/products/${productId}/resources/${resourceId}/deals",
                [
                    "json" => $deal,
                    "headers" => [
                        "Accept" => "application/json",
                        "Authorization" => "Basic {$auth}"
                    ],
                    "query" => [
                        "providerId" => $providerId
                    ]
                ]
            );
            if ($response->getStatusCode() == 200) {
                $this->logger->info("Patched deal {$deal["dealId"]} to apprien");
            } else {
                $this->logger->error("Unexpected response code: {$response->getStatusCode()}");
            }
        } catch (GuzzleException $e) {
            $this->logger->error("Failed to patch deal", ["exception" => $e]);
        }
    }
}
