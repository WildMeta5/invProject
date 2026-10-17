<?php

require "db.php";

// Save a report to the database
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the product ID and comment from the form
    $productId = $_POST['product_id'];
    $reportType = $_POST['report_type'];
    $comment = $_POST['comment'];

    // Save the report to the database
    $sql = "INSERT INTO product_reports (product_id, report_type, comment) VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $productId, $reportType, $comment);  // i for integer (product_id), s for string (comment&report_type)

    $stmt->execute();

    // Reload the report page
    header("Location: details.php?id=" . $productId);
    exit();
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
// GET REPORT HISTORY
// ---------------------------------

$sql = "SELECT * FROM product_reports
        WHERE product_id = ?
        ORDER BY id DESC";


$stmt = $conn->prepare($sql);


$stmt->bind_param(
    "i",
    $productId
);


$stmt->execute();


$reportResult = $stmt->get_result();

?>

// ---- Html --------

<!DOCTYPE html>

<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Report</title>
    <link rel="stylesheet" href="style.css">

</head>


<body>
<div class="container">


<h1>Product Report</h1>


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


<h2>Add Report</h2>


<form method="POST">

    <input
        type="hidden"
        name="product_id"
        value="<?php echo $product["id"]; ?>"
    >

    Report Type:
<br>

<select name="report_type" required>

    <option value="Cleaned">Cleaned</option>

    <option value="Tested">Tested</option>

    <option value="Damage">Damage</option>

    <option value="Repaired">Repaired</option>

    <option value="Note">Note</option>

</select>

<br><br>


    Comment:
    <br>

    <textarea
        name="comment"
        rows="5"
        cols="50"
    ></textarea>

    <br><br>


    <button type="submit">
        Save Report
    </button>


</form>


<br>


<a href="project.php">
    Back to Product Log
</a>

<hr>


<h2>Report History</h2>


<table border="1">

    <tr>
        <th>Type </th>

        <th>Comment</th>

        <th>Date / Time</th>
    </tr>


    <?php while ($report = $reportResult->fetch_assoc()) { ?>

        <tr>
            <td>
                <?php
                echo htmlspecialchars($report["report_type"]);
                ?>
            </td>

            <td>
                <?php
                echo htmlspecialchars($report["comment"]);
                ?>
            </td>

            <td>
                <?php
                echo $report["created_at"];
                ?>
            </td>

        </tr>

    <?php } ?>


</table>


</div>
</body>

</html>