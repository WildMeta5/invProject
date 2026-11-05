<?php
require "db.php";
require "ocr_helper.php";

// Delete old scan files from the uploads directory to keep it clean
deleteOldScanFiles();

$message = "";
$ocrRawText = "";
$uploadedPhotoPath = "";

// Values to keep form fields populated
$productName = "";
$serialNumber = "";
$kitNumber = "";

// --------------------------------------------------
// ACTION 0: User clicked "Cancel / Discard Scan"
// --------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action_cancel"])) {
    $oldScan = trim($_POST["previous_scan_path"] ?? "");
    if (!empty($oldScan) && file_exists($oldScan) && strpos(basename($oldScan), "scan_") === 0) {
        unlink($oldScan);
    }
    $message = "Scan cancelled and temporary photo removed.";
}

// --------------------------------------------------
// ACTION 1: A photo was uploaded -> Run OCR
// --------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action_scan"])) {
    
    // If the user is re-scanning, delete their previous temporary scan file first
    $previousScan = trim($_POST["previous_scan_path"] ?? "");
    if (!empty($previousScan) && file_exists($previousScan) && strpos(basename($previousScan), "scan_") === 0) {
        unlink($previousScan);
    }

    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == UPLOAD_ERR_OK) {
        $photo = $_FILES["photo"];
        $temporaryName = $photo["tmp_name"];

        $fileType = mime_content_type($temporaryName);
        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png"  => "png",
            "image/webp" => "webp"
        ];

        if (isset($allowedTypes[$fileType])) {
            $extension = $allowedTypes[$fileType];

            // Save temporary scan file into uploads
            $newFileName = "scan_" . uniqid() . "." . $extension;
            $photoPath = "uploads/" . $newFileName;

            if (move_uploaded_file($temporaryName, $photoPath)) {
                $uploadedPhotoPath = $photoPath;

                // Run Tesseract via our helper function
                $ocrRawText = runOCR($photoPath);

                // Auto-fill Product Name and Serial Number if parseStickerText exists
                if (function_exists('parseStickerText')) {
                    $parsed = parseStickerText($ocrRawText);
                    $productName = $parsed["product_name"];
                    $serialNumber = $parsed["serial_number"];
                }

                $message = "Photo uploaded and scanned successfully!";
            } else {
                $message = "Failed to save the uploaded image.";
            }
        } else {
            $message = "Please upload a valid JPG, PNG, or WebP image.";
        }
    } else {
        $message = "Please take or select a photo to scan.";
    }
}

// --------------------------------------------------
// ACTION 2: User confirms and clicks "Save Product"
// --------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action_save"])) {
    $productName = trim($_POST["product_name"] ?? "");
    $serialNumber = trim($_POST["serial_number"] ?? "");
    $kitNumber = (int)($_POST["kit_number"] ?? 0);
    $savedPhotoPath = trim($_POST["photo_path"] ?? "");

    if (!empty($productName) && !empty($serialNumber) && !empty($kitNumber)) {
        // 1. Insert product
        $sql = "INSERT INTO productlogs (product_name, serial_number, kit_number) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $productName, $serialNumber, $kitNumber);
        $stmt->execute();
        $newProductId = $conn->insert_id;

        // 2. Rename scan_xxx.jpg to product_ID_xxx.jpg so it's no longer marked as a temporary scan
        if (!empty($savedPhotoPath) && file_exists($savedPhotoPath)) {
            $extension = pathinfo($savedPhotoPath, PATHINFO_EXTENSION);
            $finalPhotoPath = "uploads/product_" . $newProductId . "_" . uniqid() . "." . $extension;

            // Rename the file on the hard drive
            if (rename($savedPhotoPath, $finalPhotoPath)) {
                $savedPhotoPath = $finalPhotoPath;
            }

            $sql = "INSERT INTO product_photos (product_id, photo_path) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $newProductId, $savedPhotoPath);
            $stmt->execute();
        }

        header("Location: details.php?id=" . $newProductId);
        exit;
    } else {
        $message = "Please make sure Product Name, Serial Number, and Kit are all filled out.";
        $uploadedPhotoPath = $savedPhotoPath;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Product Intake (OCR)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>New Product Intake</h1>

    <?php if (!empty($message)): ?>
        <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
    <?php endif; ?>

    <!-- STEP 1: SCAN PHOTO FORM -->
    <form method="POST" enctype="multipart/form-data">
        <h2>1. Take / Upload Sticker Photo</h2>

        <input type="hidden" name="previous_scan_path" value="<?php echo htmlspecialchars($uploadedPhotoPath); ?>">
        
        <input 
            type="file" 
            name="photo" 
            id="photoInput" 
            accept="image/*" 
            capture="environment" 
            required
        >
        <br><br>

        <button type="submit" name="action_scan">
            Upload & Scan Sticker
        </button>
    </form>

    <hr>

    <!-- STEP 2: OCR RESULTS & PRODUCT INFO VERIFICATION -->
    <?php if (!empty($uploadedPhotoPath)): ?>
        <h2>2. Review Scanned Sticker</h2>

        <div>
            <img src="<?php echo htmlspecialchars($uploadedPhotoPath); ?>" style="max-width: 100%; max-height: 250px; border-radius: 6px;">
        </div>

        <?php if (!empty($ocrRawText)): ?>
            <h3>Raw Detected Text:</h3>
            <pre style="background: #eef; padding: 10px; border-radius: 5px; white-space: pre-wrap; font-size: 14px;"><?php echo htmlspecialchars($ocrRawText); ?></pre>
        <?php endif; ?>

        <h2>3. Confirm Product Information</h2>

        <form method="POST">
            <!-- Hidden path to associate the already uploaded image -->
            <input type="hidden" name="photo_path" value="<?php echo htmlspecialchars($uploadedPhotoPath); ?>">

            <label>Product Name:</label><br>
            <input 
                type="text" 
                name="product_name" 
                value="<?php echo htmlspecialchars($productName); ?>" 
                required
            ><br><br>

            <label>Serial Number:</label><br>
            <input 
                type="text" 
                name="serial_number" 
                value="<?php echo htmlspecialchars($serialNumber); ?>" 
                autocomplete="off" 
                autocapitalize="off" 
                spellcheck="false" 
                required
            ><br><br>

            <label>Kit Number:</label><br>
            <input 
                type="number" 
                name="kit_number" 
                value="<?php echo htmlspecialchars($kitNumber ?: ''); ?>" 
                required
            ><br><br>

            <button type="submit" name="action_save">
                Confirm & Save to Inventory
            </button>
        </form>
        <!-- Allow user to discard the scan and start over -->
        <form method="POST">
            <input type="hidden" name="previous_scan_path" value="<?php echo htmlspecialchars($uploadedPhotoPath); ?>">
            <button type="submit" name="action_cancel" style="background-color: #d9534f; color: white;">
                Cancel / Discard Scan
            </button>
        </form>
    <?php endif; ?>

    <br>
    <a href="project.php">Back to Product Log</a>
</div>

</body>
</html>