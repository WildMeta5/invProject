<?php

require "db.php";

$message = "";

// ---------------------------------
// SAVE NEW PRODUCT + PHOTO
// ---------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $productName = trim($_POST["product_name"]);
    $serialNumber = trim($_POST["serial_number"]);
    $kitNumber = $_POST["kit_number"];

    // Make sure a photo was uploaded
    if (
        isset($_FILES["photo"]) &&
        $_FILES["photo"]["error"] == UPLOAD_ERR_OK
    ) {

        $photo = $_FILES["photo"];

        $temporaryName = $photo["tmp_name"];

        // Check the actual file type
        $fileType = mime_content_type($temporaryName);

        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png"  => "png",
            "image/webp" => "webp"
        ];

        // Make sure the uploaded file is an image we allow
        if (isset($allowedTypes[$fileType])) {

            $extension = $allowedTypes[$fileType];

            // ---------------------------------
            // CREATE PRODUCT
            // ---------------------------------
            $sql = "INSERT INTO productlogs
                    (product_name, serial_number, kit_number)
                    VALUES (?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ssi",
                $productName,
                $serialNumber,
                $kitNumber
            );

            $stmt->execute();

            // Get the ID MySQL just created
            $newProductId = $conn->insert_id;

            // ---------------------------------
            // SAVE PHOTO FILE
            // ---------------------------------
            $newFileName =
                "product_" .
                $newProductId .
                "_" .
                uniqid() .
                "." .
                $extension;

            $photoPath =
                "uploads/" .
                $newFileName;

            if (
                move_uploaded_file(
                    $temporaryName,
                    $photoPath
                )
            ) {

                // ---------------------------------
                // SAVE PHOTO IN DATABASE
                // --------------------------------
                $sql = "INSERT INTO product_photos
                        (product_id, photo_path)
                        VALUES (?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "is",
                    $newProductId,
                    $photoPath
                );

                $stmt->execute();

                // Go to the new product
                header(
                    "Location: details.php?id=" .
                    $newProductId
                );

                exit;

            } else {

                $message = "The product was created, but the photo could not be saved.";

            }

        } else {

            $message = "Please use a JPG, PNG, or WebP image.";

        }

    } else {

        $message = "Please take or select a photo.";

    }
}

?>

// ---- HTML ----
<!DOCTYPE html>

<html>

<head>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>New Product Intake</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<div class="container">

<h1>New Product Intake</h1>

<?php if ($message != "") { ?>

    <p>
        <?php echo htmlspecialchars($message); ?>
    </p>

<?php } ?>

<form
    method="POST"
    enctype="multipart/form-data"
>

    <h2>1. Take Product Photo</h2>

    <input
        type="file"
        name="photo"
        id="photoInput"
        accept="image/*"
        capture="environment"
        required
    >

    <br><br>

    <img 
        id="photoPreview"
        style="display:none; max-width: 100%; max-height: 300px;"
        alt="Photo Preview"
    >

    <h2>2. Product Information</h2>

    Product Name:
    <br>

    <input
        type="text"
        name="product_name"
        required
    >

    <br><br>

    Serial Number:
    <br>

    <input
        type="text"
        name="serial_number"
        autocomplete="off"
        autocapitalize="off"
        spellcheck="false"
        required
    >

    <br><br>

    Kit Number:
    <br>

    <input
        type="number"
        name="kit_number"
        required
    >

    <br><br>

    <button type="submit">
        Save Product
    </button>

</form>

<br>

<a href="project.php">
    Back to Product Log
</a>

</div>
<script>
    // Photo preview script
const photoInput = document.getElementById("photoInput");
    
const photoPreview = document.getElementById("photoPreview");
    
photoInput.addEventListener(
    "change",
    function () {
        const file = this.files[0];

        if (file) {
            photoPreview.src =
                URL.createObjectURL(file);

            photoPreview.style.display =
                "block";
        }
    }
);

</script>
</body>

</html>