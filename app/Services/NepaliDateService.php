<?php
// app/Services/NepaliDateService.php

namespace App\Services;

use App\Models\NepaliCalendar;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class NepaliDateService
{
    protected ?array $index = null;

    /** Loads all 1452 rows into two maps once per request. */
    protected function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        return $this->index = Cache::rememberForever('nepali_calendar_index', function () {
            $bs = [];   // $bs[year][month] = row
            $ad = [];   // rows sorted by start_ad_date

            foreach (NepaliCalendar::orderBy('start_ad_date')->get() as $row) {
                $bs[$row->bs_year][$row->bs_month] = $row;
                $ad[] = $row;
            }

            return ['bs' => $bs, 'ad' => $ad];
        });
    }

    /* ----------------------------------------------------------------
     * AD → BS — single indexed lookup
     * ---------------------------------------------------------------- */
    public function adToBs(int $adYear, int $adMonth, int $adDay): array
    {
        $ad = Carbon::create($adYear, $adMonth, $adDay)->startOfDay();

        // indexed range query (uses idx on start_ad_date, end_ad_date)
        $row = NepaliCalendar::where('start_ad_date', '<=', $ad)
            ->where('end_ad_date',   '>=', $ad)
            ->first();

        if (!$row) {
            throw new InvalidArgumentException(
                "AD date {$ad->toDateString()} is outside the supported BS range."
            );
        }

        $bsDay = $row->start_ad_date->diffInDays($ad) + 1;

        return [
            'year'  => $row->bs_year,
            'month' => $row->bs_month,
            'day'   => (int) $bsDay,
        ];
    }

    /* ----------------------------------------------------------------
     * BS → AD — single indexed lookup
     * ---------------------------------------------------------------- */
    public function bsToAd(int $bsYear, int $bsMonth, int $bsDay): array
    {
        $row = NepaliCalendar::where('bs_year', $bsYear)
            ->where('bs_month', $bsMonth)
            ->first();

        if (!$row) {
            throw new InvalidArgumentException(
                "BS {$bsYear}-{$bsMonth} is not in the calendar table."
            );
        }

        if ($bsDay < 1 || $bsDay > $row->total_days) {
            throw new InvalidArgumentException(
                "BS day {$bsDay} is invalid for {$bsYear}-{$bsMonth} (1–{$row->total_days})."
            );
        }

        $ad = $row->start_ad_date->copy()->addDays($bsDay - 1);

        return [
            'year'  => (int) $ad->year,
            'month' => (int) $ad->month,
            'day'   => (int) $ad->day,
        ];
    }

    /* ----------------------------------------------------------------
     * Range helpers — free with this schema, would've needed PHP loops
     * in row-per-year
     * ---------------------------------------------------------------- */
    public function monthsBetweenAd(Carbon $from, Carbon $to): array
    {
        return NepaliCalendar::where('start_ad_date', '<=', $to)
            ->where('end_ad_date',   '>=', $from)
            ->orderBy('start_ad_date')
            ->get()
            ->all();
    }

    public function monthsBetweenBs(int $fromYear, int $fromMonth, int $toYear, int $toMonth): array
    {
        return NepaliCalendar::whereRaw(
                '(bs_year * 100 + bs_month) BETWEEN ? AND ?',
                [$fromYear * 100 + $fromMonth, $toYear * 100 + $toMonth]
            )
            ->orderBy('bs_year')
            ->orderBy('bs_month')
            ->get()
            ->all();
    }

    /* ----------------------------------------------------------------
     * Small helpers
     * ---------------------------------------------------------------- */
    public function daysInMonth(int $bsYear, int $bsMonth): int
    {
        return NepaliCalendar::where('bs_year', $bsYear)
            ->where('bs_month', $bsMonth)
            ->value('total_days')
            ?? throw new InvalidArgumentException("BS {$bsYear}-{$bsMonth} not found.");
    }

    public function toNepaliDigits(string $value): string
    {
        $map = ['०','१','२','३','४','५','६','७','८','९'];
        return preg_replace_callback('/\d/', fn ($m) => $map[(int) $m[0]], $value);
    }

    public function adToBsString(int $y, int $m, int $d, bool $nepali = true): string
    {
        $bs = $this->adToBs($y, $m, $d);
        $s  = sprintf('%04d-%02d-%02d', $bs['year'], $bs['month'], $bs['day']);
        return $nepali ? $this->toNepaliDigits($s) : $s;
    }
}