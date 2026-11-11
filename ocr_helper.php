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

// 1. Grab Serial Number
    if (preg_match('/(?:Serial|S\/N|SN)\b.*?([A-Z0-9\-]{5,25})/i', $ocrText, $match)) {
        $result['serial_number'] = $match[1];
    }

    // 2. If a known product name is anywhere in the text, grab ONLY that name
    // (Add any other common product names to this list)
    $knownProducts = '/\b(AirPods\s+Pro|AirPods\s+Max|AirPods|iPhone\s*\d*(?:\s*Pro|\s*Max|\s*Plus|\s*Mini)*|iPad(?:\s*Pro|\s*Air|\s*Mini)*|Apple\s+Watch|MacBook(?:\s*Pro|\s*Air)*|ThinkPad|Latitude)\b/i';

    foreach (explode("\n", $ocrText) as $line) {
        // Skip lines that are clearly compatibility notes from the side sticker (e.g. "Apple Watch, or Mac")
        if (stripos($line, ' or ') !== false || stripos($line, 'chargers') !== false) {
            continue;
        }

        if (preg_match($knownProducts, $line, $match)) {
            $result['product_name'] = trim($match[1]);
            break;
        }
    }

    // 3. Fallback for other/non-Apple products: clean the best product line
    if ($result['product_name'] === '') {
        foreach (explode("\n", $ocrText) as $line) {
            // Skip common non-product lines
            if (preg_match('/(?:Serial|S\/N|UPC|Model|Designed|Certified|chargers|Energy)/i', $line)) {
                continue;
            }

            // Remove any part numbers (words with numbers & slashes/dashes like "MWP22AM/A") and anything before them
            $clean = preg_replace('/^.*?\b[A-Z0-9]{4,}[\/\-][A-Z0-9]+\s+/i', '', $line);

            // Cut off extra description starting with "with", "for", or "includes"
            $clean = preg_replace('/\s+(?:with|for|includes)\b.*$/i', '', $clean);

            if (strlen($clean) > 2) {
                $result['product_name'] = trim($clean);
                break;
            }
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