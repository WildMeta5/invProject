<?php

// Connect to database
require "db.php";

// Checks if you want to delete a product
if (isset($_POST["delete_id"])) {
    $deleteId = (int)$_POST["delete_id"];

    // Find all photos associated with this product and delete them from the server
    $photoSql = "SELECT photo_path FROM product_photos WHERE product_id = ?";
    $photostmt = $conn->prepare($photoSql);
    $photostmt->bind_param("i", $deleteId);
    $photostmt->execute();
    $photoResult = $photostmt->get_result();

    while ($photorRow = $photoResult->fetch_assoc()) {
        $photoPath = $photorRow['photo_path'];
        if (!empty($photoPath) && file_exists($photoPath)) {
            unlink($photoPath);
        }
    }
    $photostmt->close();

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

// Get the search text and kit filter from the URL
$search = trim($_GET["search"] ?? "");
$kit = trim($_GET["kit"] ?? "");
$kitIsValid = $kit !== "" && ctype_digit($kit);

// Filter products by search text and/or kit number
if ($search !== "" && $kitIsValid) {

    $searchTerm = "%" . $search . "%";
    $kitNumber = (int)$kit;

    $sql = $baseSelect . "
           WHERE (p.product_name LIKE ? OR p.serial_number LIKE ?)
           AND p.kit_number = ?
           ORDER BY p.id DESC";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssi",
        $searchTerm,
        $searchTerm,
        $kitNumber
    );

    $stmt->execute();

    $result = $stmt->get_result();

} elseif ($search !== "") {

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

} elseif ($kitIsValid) {

    $kitNumber = (int)$kit;

    $sql = $baseSelect . "
           WHERE p.kit_number = ?
           ORDER BY p.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kitNumber);
    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $sql = $baseSelect . "ORDER BY p.id DESC";

    $result = $conn->query($sql);

}
?>


<!DOCTYPE html>
<!-- ---- Html -------- -->
<html>

<head> <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Log</title>
<link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">

<header class="page-header">
    <div>
        <p class="eyebrow">Inventory workspace</p>
        <h1>Product Log</h1>
        <p class="page-intro">Be able to see all your products and their details.</p>
    </div>
    <a class="header-link" href="#saved-products">Browse inventory <span aria-hidden="true">↓</span></a>
</header>

<section class="add-product-panel card" id="add-product" aria-labelledby="add-product-title">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Add to inventory</p>
            <h2>Choose how to add a product</h2>
        </div>
    </div>

    <div class="add-product-options">
        <form method="POST" class="manual-form">
            <div class="option-heading">
                <span class="option-icon" aria-hidden="true">＋</span>
                <div>
                    <h3>Manual entry</h3>
                    <p>Enter the product details yourself.</p>
                </div>
            </div>

            <div class="form-grid">
                <label>
                    Product name
                    <input type="text" name="product_name" placeholder="e.g. Network switch" required>
                </label>

                <label>
                    Serial number
                    <input type="text" name="serial_number" placeholder="Enter serial number" autocomplete="off" autocapitalize="off" spellcheck="false" required>
                </label>

                <label>
                    Kit number
                    <input type="text" name="kit_number" placeholder="e.g. 1042" required>
                </label>
            </div>

            <button type="submit">Save product <span aria-hidden="true">→</span></button>
        </form>

        <div class="intake-card">
            <div class="option-heading">
                <span class="option-icon intake-icon" aria-hidden="true">✦</span>
                <div>
                    <h3>Photo intake</h3>
                    <p>Scan a sticker and let OCR fill in the details.</p>
                </div>
            </div>
            <ul class="intake-benefits">
                <li>Capture the product label</li>
                <li>Review detected information</li>
                <li>Save the photo with the product</li>
            </ul>
            <a class="intake-button" href="intake.php">Start photo intake <span aria-hidden="true">↗</span></a>
        </div>
    </div>
</section>

<!-- made an id to help with page navigation -->
<section class="inventory-section" id="saved-products">
<div class="section-heading inventory-heading">
    <div>
        <p class="eyebrow">Your catalog</p>
        <h2>Saved products</h2>
    </div>
    <span class="inventory-note">Latest additions first</span>
</div>

<form class="filter-bar" method="GET" action="project.php#saved-products">

    <label class="search-field" for="product-search">
        <span aria-hidden="true">⌕</span>
        <span class="sr-only">Search products</span>
    <input
        type="text"
        id="product-search"
        name="search"
        placeholder="Product name or serial number"
        value="<?php echo htmlspecialchars($search); ?>"
    >

    </label>

    <label class="select-field" for="kit-filter">
        <span class="sr-only">Filter by kit number</span>
    <select name="kit" id="kit-filter">
        <option value="">All kit numbers</option>
        <?php while ($kitRow = $kitResult->fetch_assoc()) { ?>
            <option
                value="<?php echo htmlspecialchars($kitRow["kit_number"]); ?>"
                <?php echo $kit === (string)$kitRow["kit_number"] ? "selected" : ""; ?>
            >
                Kit <?php echo htmlspecialchars($kitRow["kit_number"]); ?>
            </option>
        <?php } ?>
    </select>
    </label>

    <button type="submit">Filter</button>

    <a class="clear-filter" href="project.php#saved-products">Clear</a>

</form>

<div class="table-container">
<table>
    <caption class="sr-only">Saved products</caption>
<thead>
    <tr>
        <th>Product</th>
        <th>Serial number</th>
        <th>Kit</th>
        <th>Latest report</th>
        <th>Added</th>
        <th><span class="sr-only">Actions</span></th>
    </tr>
</thead>
<tbody>
    <?php while ($row = $result->fetch_assoc()) { ?>

        <tr>

            <td>
                <div class="product-cell">
                <?php if (!empty($row["latest_photo"])) { ?>
                    <a href="details.php?id=<?php echo $row["id"]; ?>">
                    <img src="<?php echo htmlspecialchars($row["latest_photo"]); ?>" alt="" class="product-thumbnail">
                    </a>
                <?php } else { ?>
                    <span class="product-thumbnail no-photo" aria-hidden="true">—</span>
                <?php } ?>
                <a class="product-name" href="details.php?id=<?php echo $row["id"]; ?>">
                    <?php echo htmlspecialchars($row["product_name"]); ?>
                </a>
                </div>
            </td>

            <td>
                <?php echo htmlspecialchars($row["serial_number"]); ?>
            </td>

            <td>
                <span class="kit-pill">Kit <?php echo htmlspecialchars($row["kit_number"]); ?></span>
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
                <time datetime="<?php echo htmlspecialchars($row["created_at"]); ?>"><?php echo htmlspecialchars($row["created_at"]); ?></time>
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
</tbody>
</table>
</div>
</section>
</body>

</html>