<?php
/**
 * Automated Verification Test Script for Wearable ERP SaaS Phase 1
 * Runs PHP syntax verification (php -l) on all application source files
 * Lead Architect - Antigravity
 */

echo "========================================================\n";
echo "WEARABLE ERP SAAS - SYNTAX & COMPILATION CHECKS\n";
echo "========================================================\n\n";

$baseDir = dirname(__DIR__);
$directories = [
    $baseDir . '/app',
    $baseDir . '/config',
    $baseDir . '/routes',
    $baseDir . '/public_html'
];

$failedFiles = [];
$totalFiles = 0;

function checkSyntax(string $path, array &$failedFiles, int &$totalFiles) {
    if (is_dir($path)) {
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                checkSyntax($path . '/' . $file, $failedFiles, $totalFiles);
            }
        }
    } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        $totalFiles++;
        $output = [];
        $returnVar = 0;
        // Run PHP lint check
        exec("php -l " . escapeshellarg($path), $output, $returnVar);
        
        if ($returnVar !== 0) {
            $failedFiles[] = [
                'file' => $path,
                'error' => implode("\n", $output)
            ];
            echo "[X] LINT FAILED: $path\n";
        }
    }
}

echo "Scanning PHP source directories...\n";
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        checkSyntax($dir, $failedFiles, $totalFiles);
    }
}

echo "\nSummary of Lint Checks:\n";
echo "--------------------------------------------------------\n";
echo "Total PHP Files Scanned: $totalFiles\n";
echo "Failed Files: " . count($failedFiles) . "\n";
echo "--------------------------------------------------------\n";

if (count($failedFiles) > 0) {
    echo "\nError Logs:\n";
    foreach ($failedFiles as $fail) {
        echo "File: " . $fail['file'] . "\n";
        echo "Error:\n" . $fail['error'] . "\n";
        echo "--------------------------------------------------------\n";
    }
    exit(1);
} else {
    echo "\nSTATUS: SUCCESS! All source files compiled successfully with zero syntax errors.\n";
    echo "========================================================\n";
    exit(0);
}
