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

<!DOCTYPE html>
 <!-- ---- Html -------- -->
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Report</title>
    <link rel="stylesheet" href="style.css">

</head>

<body>
<div class="container">

<header class="subpage-header">
    <div>
        <p class="eyebrow">Product activity</p>
        <h1>Product Report</h1>
        <p class="page-intro">Record things here.</p>
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
<p class="eyebrow">New entry</p>
<h2>Add report</h2>

<form method="POST" class="form-card">

    <input
        type="hidden"
        name="product_id"
        value="<?php echo $product["id"]; ?>"
    >

    <label>Report type
    <select name="report_type" required>

    <option value="Cleaned">Cleaned</option>

    <option value="Tested">Tested</option>

    <option value="Damage">Damage</option>

    <option value="Repaired">Repaired</option>

    <option value="Note">Note</option>

    </select>
    </label>

    <label>Comment
    <textarea
        name="comment"
        rows="5"
        cols="50"
    ></textarea>
    </label>

    <button type="submit">Save report <span aria-hidden="true">→</span></button>

</form>
</section>

<section class="history-section">
<div class="section-heading">
    <div>
        <p class="eyebrow">History</p>
        <h2>Report history</h2>
    </div>
</div>

<div class="table-container">
<table class="history-table">

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
</section>

</div>
</body>

</html>