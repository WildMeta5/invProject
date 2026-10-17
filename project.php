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
// Check if the save button was clicked, Add product
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

    // Get the ID of the newly inserted product
    $newProductId = $conn->insert_id;

    // Redirect to the product details page
    header("Location: details.php?id=" . $newProductId);
    exit;
}

// Base SQL query to get products along with their latest photo and report information
$baseSelect = "
    SELECT
        p.*,

        (
            SELECT photo_path
            FROM product_photos
            WHERE product_id = p.id
            ORDER BY id DESC
            LIMIT 1
        ) AS latest_photo,

        (
            SELECT report_type
            FROM product_reports
            WHERE product_id = p.id
            ORDER BY id DESC
            LIMIT 1
        ) AS latest_report_type,

        (
            SELECT comment
            FROM product_reports
            WHERE product_id = p.id
            ORDER BY id DESC
            LIMIT 1
        ) AS latest_report_comment,

        (
            SELECT created_at
            FROM product_reports
            WHERE product_id = p.id
            ORDER BY id DESC
            LIMIT 1
        ) AS latest_report_time

    FROM productlogs p
";

// Get the kit numbers that exist in the database
$kitSql = "SELECT DISTINCT kit_number FROM productlogs ORDER BY kit_number";

$kitResult = $conn->query($kitSql);

// Get all products from the database by
// Get the search text from the URL
$search = $_GET["search"] ?? "";

$kit = $_GET["kit"] ?? "";


// If the user searched for something
if ($search != "") {

    $searchTerm = "%" . $search . "%";

    $sql = $baseSelect . "
           WHERE p.product_name LIKE ?
           OR p.serial_number LIKE ?
           ORDER BY p.id DESC";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ss",
        $searchTerm,
        $searchTerm
    );

    $stmt->execute();

    $result = $stmt->get_result();

}


// If there is no search
else {

    $sql = $baseSelect . "ORDER BY p.id DESC";

    $result = $conn->query($sql);

}
?>

// ---- Html --------

<!DOCTYPE html>

<html>

<head> <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Log</title>
<link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">

<h1>Product Log</h1>
<a href="intake.php">
    Go to Product Intake
</a>

<h2 id="add-product"> Add Product</h2>

<form method="POST">

    Product Name:
    <br>

    <input type="text" name="product_name" required>

    <br><br>

    Serial Number:
    <br>

    <input type="text" name="serial_number" autocomplete="off" autocapitalize="off" spellcheck="false" required>

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


<form method="GET">

    Search:
    <input
        type="text"
        name="search"
        placeholder="Product name or serial number"
        value="<?php echo htmlspecialchars($_GET["search"] ?? "") ?>"
    >

    <button type="submit">
        Search
    </button>

    <a href="project.php"> show all </a>

</form>

<br>

<div class="table-container">
<table>

    <tr>
        <th>Latest Photo</th>

        <th>ID</th>

        <th>Product Name</th>

        <th>Serial Number</th>

        <th>Kit Number</th>

        <th>Latest Report</th>

        <th>Date Added</th>

        <th>Actions</th>
 
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>

        <tr>

            <td>
                <?php if ($row["latest_photo"]) { ?>
                    <a href="details.php?id=<?php echo $row["id"]; ?>">
                    <img src="<?php echo $row["latest_photo"]; ?>" alt="Latest Photo" class="product-thumbnail">
                    </a>
                <?php } else { ?>
                    No Photo
                <?php } ?>
            </td>

            <td>
                <?php echo $row["id"]; ?>
            </td>

            <td>
                <a href="details.php?id=<?php echo $row["id"]; ?>">
                <?php echo $row["product_name"]; ?>
                </a>
            </td>

            <td>
                <?php echo htmlspecialchars($row["serial_number"]); ?>
            </td>

            <td>
                <?php echo "Kit: " . $row["kit_number"]; ?>
            </td>

            <td class="latest-report">
                <?php if (!empty($row["latest_report_type"])) { ?>
                    <strong> <?php echo htmlspecialchars($row["latest_report_type"]); ?> </strong> 
                    <br>
                    <?php if (!empty($row["latest_report_comment"])) { ?>
                        <?php echo htmlspecialchars($row["latest_report_comment"]); ?>
                        <br>
                    <?php } ?>
                    <?php if (!empty($row["latest_report_time"])) { ?>
                        <small> <?php echo htmlspecialchars($row["latest_report_time"]); ?> </small>
                    <?php } ?>
                <?php } else { ?>
                    No Report
                <?php } ?>
            </td>

            <td>
                <?php echo $row["created_at"]; ?>
            </td>
                
            <td class="actions">
                <a href= "edit.php?id=<?php echo $row["id"]; ?>">
                    Edit
                </a>

                <a href="report.php?id=<?php echo $row["id"]; ?>">
                    Report
                </a>

                <a href="photo.php?id=<?php echo $row["id"]; ?>">
                    Photo
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
</div>
</body>

</html>