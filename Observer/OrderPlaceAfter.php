<?php

namespace PHZ\ApprienMagento\Observer;

use DateTime;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use PHZ\ApprienMagento\Service\ApprienApiService;
use Psr\Log\LoggerInterface;

class OrderPlaceAfter implements ObserverInterface
{
    protected LoggerInterface $logger;
    protected ApprienApiService $apprienApiService;
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        LoggerInterface $logger,
        ApprienApiService $apprienApiService,
        ScopeConfigInterface $scopeConfig,
    )
    {
        $this->logger = $logger;
        $this->apprienApiService = $apprienApiService;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(Observer $observer)
    {
        try {
            $data = $observer->getEvent()->getOrder()->getData();
            $items = $data["items"];
            foreach ($items as $item) {
                if (is_null($item["row_total_incl_tax"])) {
                    // Probably means it's a parent product
                    continue;
                }
                $locationId = $item->getStoreId();
                $productId = $item->getProductId();
                $providerId = $this->scopeConfig->getValue("apprien_pricing/provider/providerId");
                $resourceId = is_null($item->getCustomerGroupId()) ? 1 : $item->getCustomerGroupId() + 1;
                $times = $this->qtyToTimes($item->getQtyOrdered());
                $body = [
                    "productName" => $item->getName(),
                    "startTime" => $times["startTime"],
                    "endTime" => $times["endTime"],
                    "price" => round($item->getRowTotalInclTax() * 100),
                    "dealId" => $data["increment_id"]
                ];
                $this->apprienApiService->patchDeal($locationId, $productId, $resourceId, $body, $providerId);
            }
        } catch (Exception $e) {
            $this->logger->error("Apprien order placement error", ["exception" => $e]);
        }
        throw new Exception();
    }

    #[ArrayShape(["startTime" => "string", "endTime" => "string"])] private function qtyToTimes($qty): array
    {
        $duration = $qty * 15;
        $startTime = $this->roundDown(new DateTime());
        $endTime = $this->roundDown((new DateTime())->modify("+{$duration} minutes"));
        $formatString1 = "Y-m-d";
        $formatString2 = "H:i:sP";
        return [
            "startTime" => $startTime->format($formatString1) . "T" . $startTime->format($formatString2),
            "endTime" => $endTime->format($formatString1) . "T" . $endTime->format($formatString2)
        ];
    }

    private function roundDown(DateTime $date): DateTime
    {
        return date_create()->setTime(
            $date->format("H"),
            floor($date->format("i") / 15) * 15
        );
    }
}