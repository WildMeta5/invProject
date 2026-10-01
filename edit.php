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

    <title>Edit Product</title>

</head>

<body>

<h1>Edit Product</h1>

<form method="POST">

    <input type= "hidden" name= "id" value="<?php echo $product["id"]; ?>" >

    Product Name:
    <br>

    <input
        type="text"
        name="product_name"
        value="<?php echo $product["product_name"]; ?>" required >
    <br><br>

    Serial Number:
    <br>

    <input
        type="text"
        name="serial_number"
        value="<?php echo $product["serial_number"]; ?>"
        required
    >
    <br><br>

    Kit Number:
    <br>

    <input
        type="number"
        name="kit_number"
        value="<?php echo $product["kit_number"]; ?>"
        required
    >

    <br><br>

    <button type="submit">
        Save Changes
    </button>

</form>

<br>

<a href="project.php">
    Back to Product Log
</a>

</body>
</html>