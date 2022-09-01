<?php

namespace PHZ\ApprienMagento\Model;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHZ\ApprienMagento\Service\ApprienApiService;

class ProviderComment implements CommentInterface
{

    private ApprienApiService $apprienApiService;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        ApprienApiService $apprienApiService,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->apprienApiService = $apprienApiService;
        $this->scopeConfig = $scopeConfig;
    }

    public function getCommentText($elementValue)
    {
        try {
            $providers = $this->apprienApiService->getProviders();
            $count = count($providers);
            if ($count == 0) {
                return __("No companies found. Please contact your account holder.");
            }
            return __("Please select company to use for magento requests.");
        } catch (GuzzleException $e) {
            return __("Companies could not be loaded.");
        }
    }
}