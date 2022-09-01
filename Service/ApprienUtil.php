<?php

namespace PHZ\ApprienMagento\Service;

use DateTime;

class ApprienUtil
{

    private const DATE_FORMAT = "Y-m-d";
    private const TIME_FORMAT = "H:i:sP";
    private const ISO_FORMAT = "Y-m-d H:i:s";

    public static function formatTime(DateTime $dateTime): string
    {
        return $dateTime->format(self::DATE_FORMAT) . "T" . $dateTime->format(self::TIME_FORMAT);
    }

    public static function formatDateOnly(DateTime $dateTime): string
    {
        return $dateTime->format(self::DATE_FORMAT);
    }

    public static function formatTimeISO(DateTime $dateTime): string
    {
        return $dateTime->format(self::ISO_FORMAT);
    }

    public static function roundDown(DateTime $date): DateTime
    {
        return date_create()->setTime(
            $date->format("H"),
            floor($date->format("i") / 15) * 15
        );
    }
}