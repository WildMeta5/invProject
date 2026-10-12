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

        $originalName = $photo["name"];

        $temporaryName = $photo["tmp_name"];


        // Get the file extension
        $extension = pathinfo(
            $originalName,
            PATHINFO_EXTENSION
        );


        // Create a unique filename
        $newFileName =
            "photo_" .
            uniqid() .
            "." .
            $extension;


        // Where the photo will be saved
        $photoPath =
            "uploads/" .
            $newFileName;


        // Move photo into uploads folder
        move_uploaded_file(
            $temporaryName,
            $photoPath
        );


        // Save the photo path in MySQL
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


        // Reload the page
        header(
            "Location: photo.php?id=" .
            $productId
        );

        exit;
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

    <title>Product Photos</title>

</head>


<body>


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


</body>

</html>