<?php

// Runs Tesseract OCR on a given image file path and returns the text.
function runOCR($imagePath) {
    // Exact path to the Tesseract executable
    $tesseractPath = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';

    // Verify the image file actually exists
    if (!file_exists($imagePath)) { return "Error: Image file does not exist at " . $imagePath; }

    // Build the shell command:
    // escapeshellarg() wraps paths in quotes to safely handle spaces and special characters.
    // The '-' argument tells Tesseract to send output to stdout instead of writing a file.
    $command = escapeshellarg($tesseractPath) . " " 
             . escapeshellarg($imagePath) . " stdout -l eng --psm 11 2>&1";

    // Run the command and capture all output
    $output = shell_exec($command);

    return cleanBarcodeNoise($output);

    // Check if the command failed
    if ($output === null) {
        return "Error: Failed to execute Tesseract command.";
    }
    return trim($output);
}

// Remove lines that look like barcode noise from the OCR output
function cleanBarcodeNoise($text) {
    if (!$text) return "";
    $lines = explode("\n", $text);
    $cleaned = [];
    foreach ($lines as $line) {
        // Skip lines that look like barcode noise (e.g., lines with only numbers and special characters)
        if (preg_match('/^[0-9\-\.\*\+\(\)\[\]\{\}]+$/', trim($line))) {
            continue;
        }
        $cleaned[] = $line;
    }
    return implode("\n", $cleaned);
}

// Function to automatically guess the Product Name and Serial Number from the OCR text
function guessProductDetails($ocrText) {
    $result = [
        'product_name' => '',
        'serial_number' => ''
    ];
    $lines = explode("\n", $ocrText);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip empty lines
        if (empty($line)) continue;

        // Heuristic: If the line contains "Serial" or "SN", treat it as a serial number
        if (stripos($line, 'serial') !== false || stripos($line, 'sn') !== false) {
            $result['serial_number'] = preg_replace('/[^A-Za-z0-9\-]/', '', $line); // Clean up the serial number
            continue;
        }

        // Heuristic: If the line is not a serial number and is reasonably long, treat it as product name
        if (strlen($line) > 3 && empty($result['product_name'])) {
            $result['product_name'] = $line;
        }
    }
    return $result;
}

// Delete temporary scan files older than 30 minutes from the uploads directory
function deleteOldScanFiles() {
    $uploadDir = 'uploads/';
    $files = glob($uploadDir . '*');
    $now = time();

    foreach ($files as $file) {
        if (is_file($file)) {
            $fileTime = filemtime($file);
            if ($now - $fileTime > 30 * 60) { // 30 minutes
                unlink($file);
            }
        }
    }
}