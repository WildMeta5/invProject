<?php

// Connect to the database
require "db.php";

// Here makes it so it save the changes
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get information from the form
    $id = $_POST["id"];
    $product_name = $_POST["product_name"];
    $serial_number = $_POST["serial_number"];
    $kit_number = $_POST["kit_number"];

    // Update the product
    $sql = "UPDATE productlogs
            SET product_name = ?, serial_number = ?, kit_number = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssii",
        $product_name,
        $serial_number,
        $kit_number,
        $id
    );

    $stmt->execute();

    // Go back to the product page
    header("Location: project.php");
    exit;
}

// Here gets the produces to edit
// Get the product ID from the URL
$id = $_GET["id"];

// Find that product in the database
$sql = "SELECT * FROM productlogs WHERE id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $id);

$stmt->execute();

// Get the result
$result = $stmt->get_result();

$product = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="style.css">

</head>

<body>
<div class="container">

<header class="subpage-header">
    <div>
        <p class="eyebrow">Product settings</p>
        <h1>Edit Product</h1>
        <p class="page-intro">Update the information used to identify this product.</p>
    </div>
    <a class="header-link" href="project.php">Back to inventory</a>
</header>

<form method="POST" class="form-card standalone-form">

    <input type= "hidden" name= "id" value="<?php echo $product["id"]; ?>" >

    <label>Product name
    <input
        type="text"
        name="product_name"
        value="<?php echo htmlspecialchars($product["product_name"]); ?>" required >
    </label>

    <label>Serial number
    <input
        type="text"
        name="serial_number"
        value="<?php echo htmlspecialchars($product["serial_number"]); ?>"
        required
    >
    </label>

    <label>Kit number
    <input
        type="number"
        name="kit_number"
        value="<?php echo $product["kit_number"]; ?>"
        required
    >
    </label>

    <button type="submit">Save changes <span aria-hidden="true">→</span></button>

</form>

</div>
</body>
</html>