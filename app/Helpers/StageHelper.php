<?php
namespace App\Helpers;

/**
 * Universal WIP Stage Key Normalization Helper
 * Provides a single canonical source of truth for converting stage string inputs
 * into standardized system keys across API, Web Controllers, Services, and Views.
 */
class StageHelper {

    /**
     * Standardize any WIP stage string input to a canonical system key.
     * Examples:
     *   "#1 Thread Cutting" -> "thread_cutting"
     *   "Stage 1: Thread Cutting" -> "thread_cutting"
     *   "Thread Cutting" -> "thread_cutting"
     *   "thread cutting" -> "thread_cutting"
     *   "thread_cutting" -> "thread_cutting"
     */
    public static function toStageKey(string $input): string {
        $input = trim($input);
        if (empty($input)) {
            return 'general';
        }

        // Strip leading prefix like "#1 ", "1. ", "Stage 1", "Step 1"
        $clean = preg_replace('/^(#|\d+[\.\-\:\)]\s*|(Stage|Step)\s*\d+[\.\-\:\)]?\s*)/i', '', $input);
        $clean = trim((string)$clean);
        if (empty($clean)) {
            $clean = $input;
        }

        $key = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '_', $clean), '_'));
        return !empty($key) ? $key : 'general';
    }

    /**
     * Standardize any WIP stage string input to a clean human-readable title.
     * Examples:
     *   "thread_cutting" -> "Thread Cutting"
     *   "#1 Thread Cutting" -> "Thread Cutting"
     */
    public static function toStageName(string $input): string {
        $key = self::toStageKey($input);
        return ucwords(str_replace('_', ' ', $key));
    }
}
