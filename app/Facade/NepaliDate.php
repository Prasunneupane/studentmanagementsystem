<?php

namespace App\Facade;

use App\Services\NepaliDateService;
/**
 * @method static array bsToAd(int $y, int $m, int $d)
 * @method static array adToBs(int $y, int $m, int $d)
 * @method static int   daysInMonth(int $y, int $m)
 * @method static array monthsInYear(int $y)
 * @method static string adToBsString(int $y, int $m, int $d, bool $nepali = true)
 * @method static string toNepaliDigits(string $value)
 * @see \App\Services\NepaliDateService
 */
class NepaliDate
{
    protected static function getFacadeAccessor(): string
    {
        return NepaliDateService::class;
    }
}
