<?php

require "db.php";

// ---------------------------------
// GET PRODUCT ID
// ---------------------------------

$productId = $_GET["id"] ?? 0;

// Make sure the ID is a number
$productId = (int)$productId;

// ---------------------------------
// GET PRODUCT INFORMATION
// ---------------------------------
$sql = "SELECT * FROM productlogs
        WHERE id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $productId
);

$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// If product does not exist
if (!$product) { die("Product not found."); }

// ---------------------------------
// GET LATEST PHOTO
// ---------------------------------
$sql = "SELECT * FROM product_photos
        WHERE product_id = ?
        ORDER BY id DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $productId
);

$stmt->execute();
$photoResult = $stmt->get_result();
$latestPhoto = $photoResult->fetch_assoc();

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

// ---------------------------------
// GET ALL PHOTOS
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

$allPhotos = $stmt->get_result();

?>

// ------------ HTML ------------
<!DOCTYPE html>

<html>

<head>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Product Details</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<div class="container">

    <a href="project.php">
        ← Back to Product Log
    </a>

    <a href="project.php#add-product">
        Add another Product
    </a>

    <h1>
        <?php
        echo htmlspecialchars(
            $product["product_name"]
        );
        ?>
    </h1>

    <!-- MAIN PHOTO -->

    <div class="main-photo-area">

        <?php if ($latestPhoto) { ?>

            <img
                src="<?php
                echo htmlspecialchars(
                    $latestPhoto["photo_path"]
                );
                ?>"
                class="main-product-photo"
                alt="Product Photo"
            >

        <?php } else { ?>

            <p>No photo uploaded yet.</p>

        <?php } ?>

    </div>

    <!-- PRODUCT INFORMATION -->

    <div class="product-info">

        <p>
            <strong>Serial Number:</strong>

            <?php
            echo htmlspecialchars(
                $product["serial_number"]
            );
            ?>
        </p>

        <p>
            <strong>Kit:</strong>

            Kit <?php echo $product["kit_number"]; ?>
        </p>

        <p>
            <strong>Date Added:</strong>

            <?php echo $product["created_at"]; ?>
        </p>

    </div>

    <!-- ACTION BUTTONS -->

    <div class="detail-actions">

        <a href="photo.php?id=<?php echo $productId; ?>">
            Add Photo
        </a>

        <a href="edit.php?id=<?php echo $productId; ?>">
            Edit Product
        </a>

        <a href="report.php?id=<?php echo $productId; ?>">
            Add Report
        </a>

    </div>

    <hr>

    <!-- REPORT HISTORY -->
    <h2>Report History</h2>

    <div class="table-container">

        <table>

            <tr>

                <th>Type</th>

                <th>Comment</th>

                <th>Date / Time</th>

            </tr>

            <?php while ($report = $reportResult->fetch_assoc()) { ?>

                <tr>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $report["report_type"]
                        );
                        ?>

                    </td>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $report["comment"]
                        );
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

    <hr>

    <!-- PHOTO HISTORY -->
    <h2>Photos</h2>

    <div class="photo-gallery">

        <?php while ($photo = $allPhotos->fetch_assoc()) { ?>

            <div class="gallery-photo">

                <img
                    src="<?php
                    echo htmlspecialchars(
                        $photo["photo_path"]
                    );
                    ?>"
                    alt="Product Photo"
                >

                <small>
                    <?php echo $photo["created_at"]; ?>
                </small>

            </div>

        <?php } ?>

    </div>

</div>

</body>

</html>