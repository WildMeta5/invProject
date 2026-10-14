<?php

require "db.php";

// ---------------------------------
// UPLOAD PHOTO
// ---------------------------------

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

// ---------------------------------
// GET PRODUCT PHOTOS
// ---------------------------------

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

// ---- Html upload photo form --------

<!DOCTYPE html>

<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Photos</title>
    <link rel="stylesheet" href="style.css">

</head>


<body>
<div class="container">

<h1>Product Photos</h1>


<h2>
    <?php echo htmlspecialchars($product["product_name"]); ?>
</h2>


<p>
    Serial Number:
    <?php echo htmlspecialchars($product["serial_number"]); ?>
</p>


<p>
    Kit:
    <?php echo $product["kit_number"]; ?>
</p>


<hr>


<h2>Upload Photo</h2>


<form method="POST" enctype="multipart/form-data">

    <input
        type="hidden"
        name="product_id"
        value="<?php echo $product["id"]; ?>"
    >


    <input
        type="file"
        name="photo"
        accept="image/*"
        capture="environment"
        required
    >

    <br><br>


    <button type="submit">
        Upload Photo
    </button>

</form>

<hr>


<h2>Photo History</h2>


<?php while ($photo = $photoResult->fetch_assoc()) { ?>


    <div>

        <img
            src="<?php echo htmlspecialchars($photo["photo_path"]); ?>"
            width="300"
        >


        <p>
            Uploaded:
            <?php echo $photo["created_at"]; ?>
        </p>

    </div>


    <hr>


<?php } ?>


<a href="project.php">
    Back to Product Log
</a>


</div>
</body>

</html>