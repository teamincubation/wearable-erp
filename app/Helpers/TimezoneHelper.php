<?php
namespace App\Helpers;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Timezone & Relative Time Helper
 * Wearable ERP Engine
 */
class TimezoneHelper {

    /**
     * Format UTC timestamp into tenant's standard timezone
     */
    public static function formatTenantTime(?string $datetime, string $timezone = 'Asia/Kolkata', string $format = 'd M Y, h:i A'): string {
        if (empty($datetime)) {
            return 'N/A';
        }

        try {
            $dt = new DateTime($datetime, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone($timezone ?: 'Asia/Kolkata'));
            return $dt->format($format);
        } catch (Exception $e) {
            return date($format, strtotime($datetime));
        }
    }

    /**
     * Calculate human-readable "time ago" string
     */
    public static function timeAgo(?string $datetime): string {
        if (empty($datetime)) {
            return 'Just now';
        }

        try {
            $dt = new DateTime($datetime, new DateTimeZone('UTC'));
            $timestamp = $dt->getTimestamp();
        } catch (Exception $e) {
            $timestamp = strtotime($datetime);
        }

        if (!$timestamp) {
            return 'Just now';
        }

        $now = time();
        $difference = $now - $timestamp;

        if ($difference < 0) {
            return 'Just now';
        }

        if ($difference < 60) {
            return 'Just now';
        } elseif ($difference < 3600) {
            $mins = max(1, (int)floor($difference / 60));
            return $mins === 1 ? '1 min ago' : $mins . ' mins ago';
        } elseif ($difference < 86400) {
            $hours = (int)floor($difference / 3600);
            return $hours === 1 ? '1 hour ago' : $hours . ' hours ago';
        } elseif ($difference < 2592000) {
            $days = (int)floor($difference / 86400);
            return $days === 1 ? '1 day ago' : $days . ' days ago';
        } else {
            return date('d M Y', $timestamp);
        }
    }
}
