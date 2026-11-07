<?php

// Rotate sideways phone photos upright and resize huge images so Tesseract reads cleanly
function prepareImageForOCR($imagePath) {
    $img = @imagecreatefromstring(file_get_contents($imagePath));
    if (!$img) return;

    // 1. Fix sideways phone rotation using EXIF orientation tag
    $exif = @exif_read_data($imagePath);
    $angles = [3 => 180, 6 => -90, 8 => 90];
    if (!empty($exif['Orientation']) && isset($angles[$exif['Orientation']])) {
        $img = imagerotate($img, $angles[$exif['Orientation']], 0);
    }

    // 2. Shrink huge phone photos (e.g. 4000px) to 1600px wide for faster, sharper OCR
    if (imagesx($img) > 1600) {
        $img = imagescale($img, 1600);
    }

    imagejpeg($img, $imagePath, 90);
    unset($img);
}

// Run Tesseract OCR and return cleaned lines of text
function runOCR($imagePath) {
    if (!file_exists($imagePath)) return "Error: Image file not found.";

    prepareImageForOCR($imagePath);

    $tesseract = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';
    $command = escapeshellarg($tesseract) . " " . escapeshellarg($imagePath) . " stdout -l eng --psm 6 2>&1";
    $output = shell_exec($command);

    return $output ? cleanBarcodeNoise($output) : "Error: Failed to execute Tesseract.";
}

// Strip out barcode stripe hallucinations, UPC numbers, and Tesseract status messages
function cleanBarcodeNoise($text) {
    $cleaned = [];
    foreach (explode("\n", $text) as $line) {
        $line = trim($line);
        // Skip empty lines, "Estimating resolution...", or lines with no letters (like barcode numbers)
        if ($line === "" || stripos($line, "Estimating resolution") === 0 || !preg_match('/[A-Za-z]/', $line)) {
            continue;
        }
        // Skip lines with >2 barcode stripe characters (pipes, slashes, brackets)
        if (preg_match_all('/[|\\\\\/\[\]\{\}\!\~]/', $line) > 2) {
            continue;
        }
        $cleaned[] = $line;
    }
    return implode("\n", $cleaned);
}

// Extract Product Name and Serial Number from cleaned OCR text
function parseStickerText($ocrText) {
    $result = ['product_name' => '', 'serial_number' => ''];

    foreach (explode("\n", $ocrText) as $line) {
        // 1. Grab the code at the end of a line containing "Serial" or "SN" (e.g., "(S) Serial No. H3RHHL42LX2Y")
        if (preg_match('/(?:Serial|S\/N|SN)\b.*?([A-Z0-9\-]{5,25})$/i', $line, $match)) {
            $result['serial_number'] = $match[1];
            continue;
        }

        // 2. Use the first meaningful text line as the Product Name
        if ($result['product_name'] === '' && strlen($line) > 4 && !preg_match('/^(?:UPC|Model|Designed)/i', $line)) {
            // Strip leading part numbers like "MV7N2AM/A " if present
            $result['product_name'] = preg_replace('/^[A-Z0-9]{4,}\/[A-Z0-9]+\s+/i', '', $line);
        }
    }
    return $result;
}

// Delete only temporary scan_* files older than 30 minutes
function deleteOldScanFiles() {
    foreach (glob('uploads/scan_*') as $file) {
        if (is_file($file) && (time() - filemtime($file) > 1800)) {
            unlink($file);
        }
    }
}