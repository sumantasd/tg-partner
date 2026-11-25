<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;

class DateTimeHelper
{
    public const TIMEZONE = 'Asia/Kolkata';
    public const TIMEZONE_LABEL = 'IST';

    /**
     * Convert any DateTime or string to Asia/Kolkata Carbon instance.
     */
    public static function toIst(mixed $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        if ($date instanceof Carbon) {
            return $date->copy()->setTimezone(self::TIMEZONE);
        }

        if ($date instanceof DateTimeInterface) {
            return Carbon::instance($date)->setTimezone(self::TIMEZONE);
        }

        try {
            return Carbon::parse($date, self::TIMEZONE)->setTimezone(self::TIMEZONE);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Format date in standard Indian readable format: e.g. "23 Nov 2026".
     */
    public static function formatDate(mixed $date, string $format = 'd M Y'): string
    {
        $c = self::toIst($date);
        return $c ? $c->format($format) : '—';
    }

    /**
     * Format date in numeric Indian format: "DD/MM/YYYY".
     */
    public static function formatNumericDate(mixed $date): string
    {
        $c = self::toIst($date);
        return $c ? $c->format('d/m/Y') : '—';
    }

    /**
     * Format time in 12-hour AM/PM format: e.g. "05:30 PM" or "05:30 PM IST".
     */
    public static function formatTime(mixed $date, bool $includeZone = false): string
    {
        $c = self::toIst($date);
        if (!$c) {
            return '—';
        }

        return $includeZone ? $c->format('h:i A') . ' ' . self::TIMEZONE_LABEL : $c->format('h:i A');
    }

    /**
     * Format full date and time: e.g. "23 Nov 2026, 05:30 PM IST".
     */
    public static function formatDateTime(mixed $date, bool $includeZone = true): string
    {
        $c = self::toIst($date);
        if (!$c) {
            return '—';
        }

        $base = $c->format('d M Y, h:i A');
        return $includeZone ? "{$base} " . self::TIMEZONE_LABEL : $base;
    }

    /**
     * Format relative time: e.g. "2 hours ago".
     */
    public static function formatRelative(mixed $date): string
    {
        $c = self::toIst($date);
        return $c ? $c->diffForHumans() : '—';
    }

    /**
     * Format amount in Indian Currency representation (e.g., ₹1,50,000.00).
     */
    public static function formatIndianCurrency(mixed $amount, int $decimals = 2): string
    {
        if ($amount === null || $amount === '') {
            return '₹0.00';
        }

        $num = (float) $amount;
        $isNegative = $num < 0;
        $abs = abs($num);

        $parts = explode('.', number_format($abs, $decimals, '.', ''));
        $intPart = $parts[0];
        $decPart = isset($parts[1]) ? '.' . $parts[1] : '';

        // Indian numbering grouping: last 3 digits, then groups of 2
        if (strlen($intPart) > 3) {
            $lastThree = substr($intPart, -3);
            $remaining = substr($intPart, 0, -3);
            $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
            $formattedInt = $remaining . ',' . $lastThree;
        } else {
            $formattedInt = $intPart;
        }

        return ($isNegative ? '-₹' : '₹') . $formattedInt . $decPart;
    }
}
