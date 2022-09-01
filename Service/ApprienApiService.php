<?php

namespace PHZ\ApprienMagento\Service;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHZ\ApprienMagento\Model\Deal;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ApprienApiService
{
    const API_URL = 'https://api.apprien.com/api/1/';
    private Client $client;
    private LoggerInterface $logger;
    private ScopeConfigInterface $scopeConfig;
    private string $accessToken = "";
    private string $tokenType = "";
    private int $accessTokenExpires;


    public function __construct(
        LoggerInterface      $logger,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->logger = $logger;
        $this->client = new Client([
            "base_uri" => self::API_URL
        ]);
        $this->scopeConfig = $scopeConfig;
        $this->logout();
    }

    private function refreshAuth()
    {
        $clientId = $this->scopeConfig->getValue("apprien_pricing/authentication/clientId");
        $clientSecret = $this->scopeConfig->getValue("apprien_pricing/authentication/clientSecret");
        $authString = "{$clientId}:{$clientSecret}";
        $auth = base64_encode($authString);
        $response = $this->client->request(
            "POST",
            "user/login/client_credentials",
            [
                "headers" => [
                    "Authorization" => "Basic {$auth}"
                ]
            ]
        );
        $response = json_decode($response->getBody()->getContents(), true);
        $this->accessToken = $response["data"]["access_token"];
        $this->tokenType = $response["data"]["token_type"];
        $this->accessTokenExpires = time() + $response["data"]["expires_in"];
    }

    /**
     * @throws GuzzleException
     */
    private function request(string $method, $uri = "", array $options = []): ResponseInterface
    {
        if (empty($this->accessToken) || $this->accessTokenExpires < time()) {
            $this->refreshAuth();
        }

        if (!array_key_exists("headers", $options)) {
            $options["headers"] = [];
        }

        $options["headers"]["Authorization"] = "$this->tokenType $this->accessToken";

        return $this->client->request($method, $uri, $options);
    }

    /**
     * @throws GuzzleException
     */
    private function providerRequest(string $method, $uri = "", array $options = []): ResponseInterface
    {
        $providerId = $this->scopeConfig->getValue("apprien_pricing/provider/providerId");

        if (!array_key_exists("query", $options)) {
            $options["query"] = [];
        }

        $options["query"]["providerId"] = $providerId;
        return $this->request($method, $uri, $options);
    }

    /**
     * @throws GuzzleException
     */
    public function patchDeal(int $locationId, int $productId, int $resourceId, Deal $deal)
    {
        $json = $deal->toJson();
        $this->providerRequest(
            "PATCH",
            "locations/{$locationId}/products/{$productId}/resources/{$resourceId}/deals",
            [
                "json" => $deal->toJson(),
                "headers" => [
                    "Accept" => "application/json",
                ]
            ]
        );
    }

    /**
     * @throws GuzzleException
     */
    public function getPrices($productId, DateTime $from, DateTime $to)
    {
        $fromRounded = ApprienUtil::roundDown($from);
        $toRounded = ApprienUtil::roundDown($to);
        $response = $this->providerRequest(
            "GET",
            "prices/{$productId}",
            [
                "query" => [
                    "startTime" => ApprienUtil::formatTimeISO($fromRounded),
                    "endTime" => ApprienUtil::formatTimeISO($toRounded)
                ]
            ]
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws GuzzleException
     */
    public function getProviders(): array
    {
        $response = $this->request("GET", "user/providers");
        $body = json_decode($response->getBody()->getContents(), true);
        return $body["data"];
    }

    /**
     * @throws GuzzleException
     */
    public function getProducts(): array
    {
        $response = $this->providerRequest("GET", "products");
        return json_decode($response->getBody()->getContents(), true)["data"];
    }

    /**
     * @throws GuzzleException
     */
    public function getProduct(int $productId): array
    {
        $response = $this->providerRequest("GET", "products/{$productId}");
        return json_decode($response->getBody()->getContents(), true)["data"];
    }

    /**
     * @throws GuzzleException
     */
    public function getProductSales(int $productId, DateTime $from, DateTime $to): array
    {
        $response = $this->providerRequest(
            "GET",
            "productsales/{$productId}",
            [
                "query" => [
                    "startTime" => ApprienUtil::formatDateOnly($from),
                    "endTime" => ApprienUtil::formatDateOnly($to)
                ]
            ]
        );
        return json_decode($response->getBody()->getContents(), true)["data"];
    }

    public function logout() {
        $this->accessToken = "";
        $this->tokenType = "";
        $this->accessTokenExpires = time();
    }

    public function isLoggedIn(): bool {
        try {
            $this->request("POST", "authorize");
            return true;
        } catch (GuzzleException $e) {
            return false;
        }
    }
}
