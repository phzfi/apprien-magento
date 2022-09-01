<?php

namespace PHZ\ApprienMagento\Model\Config\Source;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Data\OptionSourceInterface;
use PHZ\ApprienMagento\Service\ApprienApiService;
use Psr\Log\LoggerInterface;

class Provider implements OptionSourceInterface {

    private ApprienApiService $apprienApiService;
    private LoggerInterface $logger;

    public function __construct(
        ApprienApiService $apprienApiService,
        LoggerInterface $logger
    )
    {
        $this->apprienApiService = $apprienApiService;
        $this->logger = $logger;
    }

    public function toOptionArray(): array
    {
        try {
            $providers = $this->apprienApiService->getProviders();
            return array_map(fn($provider): array => [
                "value" => $provider["id"],
                "label" => $provider["name"]
            ], $providers);
        } catch (GuzzleException $e) {
            $this->logger->error("Error in convert to option array", ["exception" => $e]);
        }
        return [];
    }
}