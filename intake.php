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

// ACTION 0: User clicked "Cancel / Discard Scan"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action_cancel"])) {
    $oldScan = trim($_POST["previous_scan_path"] ?? "");
    if (!empty($oldScan) && file_exists($oldScan) && strpos(basename($oldScan), "scan_") === 0) {
        unlink($oldScan);
    }
    $message = "Scan cancelled and temporary photo removed.";
}

// ACTION 1: A photo was uploaded -> Run OCR
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

// ACTION 2: User confirms and clicks "Save Product"
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
    <header class="subpage-header">
        <div>
            <p class="eyebrow">Trust the process</p>
            <h1>New Product Intake</h1>
            <p class="page-intro">Scan a product label to capture its details faster.</p>
        </div>
        <a class="header-link" href="project.php">Back to inventory</a>
    </header>

    <?php if (!empty($message)): ?>
        <p class="status-message"><strong><?php echo htmlspecialchars($message); ?></strong></p>
    <?php endif; ?>

    <!-- 1: SCAN PHOTO FORM -->
    <section class="workflow-card">
        <div class="section-heading">
            <div>
                <p class="eyebrow">1st Thing to do...</p>
                <h2>Take or upload a sticker photo</h2>
                <p class="page-intro">Make sure the sticker is close to the camera and in bright light, clear and readable! Might make mistakes if not done correctly.</p>
            </div>
            <span class="step-badge">Scan</span>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="previous_scan_path" value="<?php echo htmlspecialchars($uploadedPhotoPath); ?>">

            <label class="upload-field" for="photoInput">
                <span class="upload-icon" aria-hidden="true">↑</span>
                <span>
                    <strong>Choose a clear label photo</strong>
                    <small>JPG, PNG, or WebP · Camera capture supported</small>
                </span>
                <input type="file" name="photo" id="photoInput" accept="image/*" capture="environment" required>
            </label>

            <button type="submit" name="action_scan">Upload &amp; scan sticker <span aria-hidden="true">→</span></button>
        </form>
    </section>

    <!-- 2: OCR RESULTS & PRODUCT INFO VERIFICATION -->
    <?php if (!empty($uploadedPhotoPath)): ?>
        <section class="workflow-card scan-results">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Step 2</p>
                <h2>Review scanned sticker</h2>
            </div>
            <span class="step-badge">Review</span>
        </div>

        <div class="scan-preview">
            <img src="<?php echo htmlspecialchars($uploadedPhotoPath); ?>" alt="Uploaded product sticker">
        </div>

        <?php if (!empty($ocrRawText)): ?>
            <h3>Detected text</h3>
            <pre class="ocr-output"><?php echo htmlspecialchars($ocrRawText); ?></pre>
        <?php endif; ?>

        <div class="confirm-section">
        <p class="eyebrow">Step 3</p>
        <h2>Confirm product information</h2>

        <form method="POST" class="form-card">
            <!-- Hidden path to associate the already uploaded image -->
            <input type="hidden" name="photo_path" value="<?php echo htmlspecialchars($uploadedPhotoPath); ?>">

            <label>Product name
            <input 
                type="text" 
                name="product_name" 
                value="<?php echo htmlspecialchars($productName); ?>" 
                required
            >
            </label>

            <label>Serial number
            <input 
                type="text" 
                name="serial_number" 
                value="<?php echo htmlspecialchars($serialNumber); ?>" 
                autocomplete="off" 
                autocapitalize="off" 
                spellcheck="false" 
                required
            >
            </label>

            <label>Kit number
            <input 
                type="number" 
                name="kit_number" 
                value="<?php echo htmlspecialchars($kitNumber ?: ''); ?>" 
                required
            >
            </label>

            <button type="submit" name="action_save">Confirm &amp; save to inventory <span aria-hidden="true">→</span></button>
        </form>
        <!-- Allow user to discard the scan and start over -->
        <form method="POST" class="discard-form">
            <input type="hidden" name="previous_scan_path" value="<?php echo htmlspecialchars($uploadedPhotoPath); ?>">
            <button class="danger-button" type="submit" name="action_cancel">
                Cancel / Discard Scan
            </button>
        </form>
        </div>
        </section>
    <?php endif; ?>

</div>

</body>
</html>