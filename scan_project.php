<?php
// filepath: c:\xampp\htdocs\tourops\scan_project.php

/**
 * Script to scan the project folder for syntax errors and coding issues.
 * Outputs results to 'error_report.txt'.
 */

// Define the project folder to scan
$projectFolder = __DIR__;
$outputFile = $projectFolder . DIRECTORY_SEPARATOR . 'error_report.txt';

// Initialize the output file
file_put_contents($outputFile, "Error Report for Project: $projectFolder\n\n");

// Function to recursively scan files
function scanFiles($folder, $outputFile)
{
    $files = scandir($folder);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $filePath = $folder . DIRECTORY_SEPARATOR . $file;

        if (is_dir($filePath)) {
            // Recursively scan subdirectories
            scanFiles($filePath, $outputFile);
        } else {
            // Check file type and run appropriate checks
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);

            switch ($extension) {
                case 'php':
                    checkPHPSyntax($filePath, $outputFile);
                    break;
                case 'js':
                    checkJavaScript($filePath, $outputFile);
                    break;
                case 'css':
                    checkCSS($filePath, $outputFile);
                    break;
                default:
                    // Skip unsupported file types
                    break;
            }
        }
    }
}

// Function to check PHP syntax
function checkPHPSyntax($filePath, $outputFile)
{
    $command = "php -l " . escapeshellarg($filePath);
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        file_put_contents($outputFile, "Errors in $filePath:\n" . implode("\n", $output) . "\n\n", FILE_APPEND);
    }
}

// Function to check JavaScript files using ESLint
function checkJavaScript($filePath, $outputFile)
{
    $command = "eslint " . escapeshellarg($filePath) . " --quiet";
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        file_put_contents($outputFile, "Errors in $filePath:\n" . implode("\n", $output) . "\n\n", FILE_APPEND);
    }
}

// Function to check CSS files using CSSLint
function checkCSS($filePath, $outputFile)
{
    $command = "csslint " . escapeshellarg($filePath) . " --quiet";
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        file_put_contents($outputFile, "Errors in $filePath:\n" . implode("\n", $output) . "\n\n", FILE_APPEND);
    }
}

// Start scanning the project folder
scanFiles($projectFolder, $outputFile);

echo "Scan complete. Results saved to $outputFile.\n";