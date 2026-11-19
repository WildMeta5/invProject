<?php

require "db.php";

// Upload a photo
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $productId = $_POST["product_id"];

    // Make sure a file was uploaded
    if (isset($_FILES["photo"])) {

        $photo = $_FILES["photo"];

        // Make sure the upload worked
        if ($photo["error"] == UPLOAD_ERR_OK) {

            $temporaryName = $photo["tmp_name"];

            // Check the real file type
            $fileType = mime_content_type($temporaryName);

            // File types we will allow
            $allowedTypes = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            // Make sure it is an allowed image type
            if (isset($allowedTypes[$fileType])) {

                $extension = $allowedTypes[$fileType];

                // Create a unique file name
                $newFileName =
                    "photo_" .
                    uniqid() .
                    "." .
                    $extension;

                $photoPath =
                    "uploads/" .
                    $newFileName;

                // Move the uploaded image
                if (move_uploaded_file(
                    $temporaryName,
                    $photoPath
                )) {

                    // Save the image path in MySQL
                    $sql = "INSERT INTO product_photos
                            (product_id, photo_path)
                            VALUES (?, ?)";

                    $stmt = $conn->prepare($sql);

                    $stmt->bind_param(
                        "is",
                        $productId,
                        $photoPath
                    );

                    $stmt->execute();

                    header(
                        "Location: photo.php?id=" .
                        $productId
                    );
                    exit;
                }
            } else {
                echo "Please upload a JPG, PNG, or WebP image.";
            }
        } else {
            echo "There was a problem uploading the photo.";
        }
    }
}

// Get the product ID from the URL
$productId = $_GET["id"];

// Find the product
$sql = "SELECT * FROM productlogs
        WHERE id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $productId);

$stmt->execute();

$result = $stmt->get_result();

$product = $result->fetch_assoc();

// Get all photos for the product (again)
$sql = "SELECT * FROM product_photos
        WHERE product_id = ?
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $productId
);

$stmt->execute();

$photoResult = $stmt->get_result();

?>

<!DOCTYPE html>
<!------ Html upload photo form -------->
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Photos</title>
    <link rel="stylesheet" href="style.css">

</head>

<body>
<div class="container">

<header class="subpage-header">
    <div>
        <p class="eyebrow">Product media</p>
        <h1>Product Photos</h1>
        <p class="page-intro">Add reference images to this product record.</p>
    </div>
    <div class="header-actions">
        <a class="header-link" href="project.php">Back to inventory</a>
        <a class="header-link" href="details.php?id=<?php echo $product["id"]; ?>">See details for this product</a>
    </div>
</header>

<div class="product-summary">
    <strong><?php echo htmlspecialchars($product["product_name"]); ?></strong>
    <span>Serial <?php echo htmlspecialchars($product["serial_number"]); ?></span>
    <span class="kit-pill">Kit <?php echo htmlspecialchars($product["kit_number"]); ?></span>
</div>

<section class="workflow-card">
<p class="eyebrow">New photo</p>
<h2>Upload photo</h2>

<form method="POST" class="form-card" enctype="multipart/form-data">

    <input
        type="hidden"
        name="product_id"
        value="<?php echo $product["id"]; ?>"
    >

    <label class="upload-field" for="product-photo">
        <span class="upload-icon" aria-hidden="true">↑</span>
        <span>
            <strong>Choose a product photo</strong>
            <small>JPG, PNG, or WebP · Camera capture supported</small>
        </span>
        <input type="file" name="photo" id="product-photo" accept="image/*" capture="environment" required>
    </label>

    <button type="submit">Upload photo <span aria-hidden="true">→</span></button>

</form>
</section>

<section class="history-section">
<div class="section-heading">
    <div>
        <p class="eyebrow">Media library</p>
        <h2>Photo history</h2>
    </div>
</div>

<div class="photo-history">
<?php while ($photo = $photoResult->fetch_assoc()) { ?>

    <figure class="history-photo">

        <img
            src="<?php echo htmlspecialchars($photo["photo_path"]); ?>"
            alt="Product photo"
        >

        <figcaption>Uploaded <?php echo htmlspecialchars($photo["created_at"]); ?></figcaption>

    </figure>

<?php } ?>
</div>
</section>

</div>
</body>

</html>