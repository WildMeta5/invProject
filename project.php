<?php

//Connect to database
require "db.php";

// Check if the save button was clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process what the user typed 
    $product_name = $_POST['product_name'];
    $serial_number = $_POST['serial_number'];

    // Save the product information to the database
    $sql = "INSERT INTO productlogs (product_name, serial_number) VALUES (?, ?)";

    $stmt = $conn->prepare($sql);
    // put the values into the sql command
    $stmt->bind_param("ss", $product_name, $serial_number);

    $stmt->execute();
}

// Get all products from the database
$sql = "SELECT * FROM productlogs ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>

<html>

<head> <title>Product Log</title> </head>

<body>

<h1>Product Log</h1>

<h2>Add Product</h2>

<form method="POST">

    Product Name:
    <br>

    <input type="text" name="product_name" required>

    <br><br>

    Serial Number:
    <br>

    <input type="text" name="serial_number" required>

    <br><br>

    <button type="submit">
        Save Product
    </button>

</form>

<hr>

<h2>Saved Products</h2>

<table border="1">

    <tr>

        <th>ID</th>

        <th>Product Name</th>

        <th>Serial Number</th>

        <th>Date / Time</th>

    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>

        <tr>

            <td>
                <?php echo $row["id"]; ?>
            </td>

            <td>
                <?php echo $row["product_name"]; ?>
            </td>

            <td>
                <?php echo $row["serial_number"]; ?>
            </td>

            <td>
                <?php echo $row["created_at"]; ?>
            </td>

        </tr>


    <?php } ?>

</table>

</body>

</html>