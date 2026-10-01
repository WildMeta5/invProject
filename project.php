<?php

// Connect to database
require "db.php";

// Checks if you want to delete a product
if (isset($_POST["delete_id"])) {
    $deleteId = $_POST["delete_id"];

    $sql= "DELETE FROM productlogs WHERE id = ?";
    // Prepare
    $stmt = $conn->prepare($sql);
    // put id to "i"/?
    $stmt->bind_param("i", $deleteId);
    // run it
    $stmt->execute();
}
// Check if the save button was clicked
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process what the user typed 
    $product_name = $_POST['product_name'];
    $serial_number = $_POST['serial_number'];
    $kit_number = $_POST['kit_number'];

    // Save the product information to the database
    $sql = "INSERT INTO productlogs (product_name, serial_number, kit_number) VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);
    // put the values into the sql command
    $stmt->bind_param("ssi", $product_name, $serial_number, $kit_number);

    $stmt->execute();
}

// Get all products from the database
$sql = "SELECT * FROM productlogs ORDER BY id DESC";
$result = $conn->query($sql);
?>

// ---- Html --------

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

    Kit Number:
    <br>

    <input type="text" name="kit_number" required>

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

        <th>Kit Number</th>

        <th>Date / Time</th>

        <th>Actions</th>
 
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
                <?php echo "kit " . $row["kit_number"]; ?>
            </td>

            <td>
                <?php echo $row["created_at"]; ?>
            </td>
                
            <td>
                <a href= "edit.php?id=<?php echo $row["id"]; ?>">
                    Edit
                </a>

                <form method="POST">
                    <input type= "hidden" name= "delete_id" value= "<?php echo $row["id"]; ?>" >

                    <button type= "submit"
                        onclick="return confirm('Are you sure you want to delete this?')" >
                        Delete
                    </button>
                </form>
            </td>
        </tr>


    <?php } ?>

</table>

</body>

</html>