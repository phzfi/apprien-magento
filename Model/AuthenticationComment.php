<?php

namespace PHZ\ApprienMagento\Model;

use Magento\Config\Model\Config\CommentInterface;
use PHZ\ApprienMagento\Service\ApprienApiService;

class AuthenticationComment implements CommentInterface
{
    private ApprienApiService $apprienApiService;

    public function __construct(
        ApprienApiService $apprienApiService
    )
    {
        $this->apprienApiService = $apprienApiService;
    }

    public function getCommentText($elementValue)
    {
        if (!$this->apprienApiService->isLoggedIn()) {
            return __("Invalid credentials. Please check Client ID and Client secret.");
        }
        return __("Correct credentials entered.");
    }
}