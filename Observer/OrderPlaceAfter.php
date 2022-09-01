<?php

namespace PHZ\ApprienMagento\Observer;

use DateTime;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use PHZ\ApprienMagento\Model\Deal;
use PHZ\ApprienMagento\Service\ApprienApiService;
use Psr\Log\LoggerInterface;
use Throwable;

class OrderPlaceAfter implements ObserverInterface
{
    protected LoggerInterface $logger;
    protected ApprienApiService $apprienApiService;
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        LoggerInterface      $logger,
        ApprienApiService    $apprienApiService,
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
                $resourceId = is_null($item->getCustomerGroupId()) ? 1 : $item->getCustomerGroupId() + 1;
                $deal = new Deal(
                    $item->getName(),
                    $item->getQtyOrdered(),
                    round($item->getRowTotalInclTax() * 100),
                    intval($data["increment_id"])
                );
                $this->apprienApiService->patchDeal($locationId, $productId, $resourceId, $deal);

                // test Price??
                $prices = $this->apprienApiService->getPrices($productId, new DateTime(), (new DateTime())->modify("+30 minutes"));
                $this->logger->info("Prices", ["prices" => print_r($prices, true)]);

                // log products
                $products = $this->apprienApiService->getProducts();
                $this->logger->info("Products", ["products" => print_r($products, true)]);

                // log product
                $product = $this->apprienApiService->getProduct($productId);
                $this->logger->info("Product", ["products" => print_r($product, true)]);

                // log sales
                $sales = $this->apprienApiService->getProductSales(
                    $productId,
                    (new DateTime())->modify("-1 month"),
                    new DateTime()
                );
                $this->logger->info("Sales", ["sales" => print_r($sales, true)]);
            }
        } catch (Throwable $e) {
            $this->logger->error("Apprien order placement error", ["exception" => $e]);
        }
    }
}