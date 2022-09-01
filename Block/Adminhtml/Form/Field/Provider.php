<?php

namespace PHZ\ApprienMagento\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHZ\ApprienMagento\Service\ApprienApiService;
use Psr\Log\LoggerInterface;

class Provider extends Field
{
    private ApprienApiService $apprienApiService;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        ApprienApiService $apprienApiService,
        LoggerInterface $logger,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    )
    {
        parent::__construct($context, $data, $secureRenderer);
        $this->apprienApiService = $apprienApiService;
        $this->logger = $logger;
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        $loggedIn = $this->apprienApiService->isLoggedIn();
        if (!$this->apprienApiService->isLoggedIn()) {
            $element->setData("readonly", 1);
        }
        return parent::_getElementHtml($element);
    }
}