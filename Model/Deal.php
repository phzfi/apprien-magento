<?php

namespace PHZ\ApprienMagento\Model;

use DateTime;
use PHZ\ApprienMagento\Service\ApprienUtil;

class Deal
{

    private string $name;
    private int $qty;
    private int $price;
    private string $dealId;
    private DateTime $startTime;
    private DateTime $endTime;

    public function __construct(
        string $name,
        int $qty,
        int $price,
        string $dealId
    )
    {
        $this->name = $name;
        $this->qty = $qty;
        $this->price = $price;
        $this->dealId = $dealId;
        $times = $this->qtyToTimes($this->qty);
        $this->startTime = $times["startTime"];
        $this->endTime = $times["endTime"];
    }

    public function toJson(): array
    {
        return [
            "productName" => $this->name,
            "startTime" => ApprienUtil::formatTime($this->startTime),
            "endTime" => ApprienUtil::formatTime($this->endTime),
            "price" => $this->price,
            "dealId" => $this->dealId
        ];
    }

    private function qtyToTimes($qty): array
    {
        $duration = $qty * 15;
        $startTime = ApprienUtil::roundDown(new DateTime());
        $endTime = ApprienUtil::roundDown((new DateTime())->modify("+{$duration} minutes"));
        return [
            "startTime" => $startTime,
            "endTime" => $endTime
        ];
    }
}