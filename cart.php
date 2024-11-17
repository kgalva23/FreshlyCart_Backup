<?php
include("functions.php");
include("components/header.php");
include("components/footer.php");
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

not_logged();
$_SESSION["active_page"] = "Cart";

$dblink = db_connect();

// Handle item removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $itemId = $_POST['item_id'];
    $userId = $_SESSION['user_id'];

    // Remove the item from the cart_item table
    $sql = "DELETE FROM cart_item WHERE user_id = :user_id AND item_id = :item_id";
    $stmt = $dblink->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
    $stmt->execute();

    // Redirect to avoid form resubmission issues
    header("Location: cart.php");
    exit();
}

// Fetch cart items from the database for the logged-in user
$userId = $_SESSION['user_id'];
$sql = "SELECT ci.item_id, ci.quantity, i.name, i.price, i.description, i.company, i.stock, i.image_id
        FROM cart_item ci
        JOIN item i ON ci.item_id = i.item_id
        WHERE ci.user_id = :user_id";
$stmt = $dblink->prepare($sql);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$itemDetails = [];
$totalPrice = 0;

if (!empty($cartItems)) {
    foreach ($cartItems as $row) {
        $row['total'] = $row['price'] * $row['quantity'];
        $totalPrice += $row['total'];
        $itemDetails[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="/style.css" rel="stylesheet">
    <title>Shopping Cart</title>
    <style>
        .cart-items {
            list-style-type: none;
            padding: 0;
        }

        .cart-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
        }

        .cart-item h3 {
            margin-top: 0;
        }
    </style>
</head>

<body>
    <?php generate_header(); ?>

    <div class="container">
        <h1>Shopping Cart</h1>
        <?php if (empty($itemDetails)): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($itemDetails as $item): ?>
                    <div class="row">
                        <div class="col-md-8 col-lg-6 mx-auto">
                            <div class="cart-item">
                                <h3>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </h3>
                                <p>Quantity:
                                    <?php echo htmlspecialchars($item['quantity']); ?>
                                </p>
                                <p class="item-price">
                                    $<?php echo htmlspecialchars(number_format($item['price'], 2)); ?> each
                                </p>
                                <p>Total: $<?php echo htmlspecialchars(number_format($item['total'], 2)); ?></p>
                                <!-- Button to remove the item -->
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <button type="submit" class="btn btn-danger">Remove from Cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="cart-total">
                    <h3 id="totalPrice">Total Price: $<?php echo number_format($totalPrice, 2); ?></h3>
                    <button class="checkout" id="checkoutButton"
                        onclick="window.location.href='checkout.php';">Checkout</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php generate_footer(); ?>
</body>

</html>
