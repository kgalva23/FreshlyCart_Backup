<?php
include("functions.php");
include("components/header.php");
include("components/footer.php");
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

not_logged();
$_SESSION["active_page"] = "Home";

$dblink = db_connect();

// Handle Add to Cart functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $userId = $_SESSION['user_id'];
    $itemId = $_POST['item_id'];

    // Check if the item is already in the cart
    $sql = "SELECT quantity FROM cart_item WHERE user_id = :user_id AND item_id = :item_id";
    $stmt = $dblink->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
    $stmt->execute();
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cartItem) {
        // Update the quantity if the item is already in the cart
        $sql = "UPDATE cart_item 
                SET quantity = quantity + 1 
                WHERE user_id = :user_id AND item_id = :item_id";
    } else {
        // Insert a new item into the cart
        $sql = "INSERT INTO cart_item (user_id, item_id, quantity) 
                VALUES (:user_id, :item_id, 1)";
    }

    $stmt = $dblink->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $successMessage = "Item successfully added to your cart.";
    } else {
        $errorMessage = "Failed to add item to cart. Please try again.";
    }
}

// Fetch items from the database
$sql = "SELECT item.*, image.image AS ImagePath 
        FROM item 
        LEFT JOIN image ON item.image_id = image.image_id::text";

$stmt = $dblink->prepare($sql);
$stmt->execute();
$items = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $items[] = $row;
}

$dblink = null; // Close the PDO connection
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/style.css" rel="stylesheet">
    <title>Home Page</title>
    <style>
        .card-flex {
            display: flex;
            flex-direction: row;
            align-items: center;
        }

        .card-flex img {
            width: 150px;
            height: auto;
            margin-right: 15px;
        }

        .card-body {
            flex-grow: 1;
            align-items: center;
        }
    </style>
</head>

<body>
    <?php generate_header(); ?>

    <div class="container mt-5">
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php elseif (isset($errorMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <h2 class="mb-4">Popular Items</h2>
        <div class="row">
            <?php foreach ($items as $item): ?>
                <div class="col-md-6 mb-4">
                    <div class="card card-flex">
                        <img src="<?= isset($item['ImagePath']) ? htmlspecialchars($item['ImagePath']) : 'images/' . htmlspecialchars($item['name']) . '.jpg'; ?>"
                             alt="Item Image" height="50" width="50" class="rounded-circle">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($item['description']) ?></p>
                            <p class="card-text">Company: <?= htmlspecialchars($item['company']) ?></p>
                            <p class="card-text">Price: $<?= htmlspecialchars(number_format($item['price'], 2)) ?></p>
                            <p class="card-text">Available: <?= htmlspecialchars($item['stock']) ?></p>
                            <form method="post">
                                <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['item_id']) ?>">
                                <button type="submit" class="btn btn-primary">Add to Cart</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php generate_footer(); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
