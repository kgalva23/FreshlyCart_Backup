<?php
include("functions.php");
include("components/header.php");
include("components/footer.php");
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

not_logged();
$_SESSION["active_page"] = "Items";

// Connect to the database
$dblink = db_connect();

// Determine sorting order based on the query parameter
$sortOrder = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$ascending = ($sortOrder === 'price_low_high') ? 'ASC' : 'DESC';

try {
    $sql = "SELECT item.*, image.image AS ImagePath
            FROM item
            LEFT JOIN image ON item.image_id = image.image_id::text
            ORDER BY item.price $ascending";
    $stmt = $dblink->prepare($sql);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Add to Cart handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $userId = $_SESSION['user_id'];
    $itemId = $_POST['item_id'];

    try {
        // Check if the item is already in the cart
        $checkSql = "SELECT quantity FROM cart_item WHERE user_id = :user_id AND item_id = :item_id";
        $checkStmt = $dblink->prepare($checkSql);
        $checkStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $checkStmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $checkStmt->execute();
        $cartItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($cartItem) {
            // Update quantity if item is already in the cart
            $updateSql = "UPDATE cart_item SET quantity = quantity + 1 WHERE user_id = :user_id AND item_id = :item_id";
            $updateStmt = $dblink->prepare($updateSql);
        } else {
            // Insert a new item if it is not in the cart
            $updateSql = "INSERT INTO cart_item (user_id, item_id, quantity) VALUES (:user_id, :item_id, 1)";
            $updateStmt = $dblink->prepare($updateSql);
        }

        $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $updateStmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $updateStmt->execute();

        // Set the success message in the session
        $_SESSION['success_message'] = "Item successfully added to your cart.";

        // Redirect to avoid form resubmission issues
        header("Location: items.php?sort=$sortOrder");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success" role="alert">
        <?= htmlspecialchars($_SESSION['success_message']); ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="/components/footer.css" rel="stylesheet">
    <link href="/style.css" rel="stylesheet">
    <title>Items Page</title>
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
        }
        .openModalBtn {
            background-color: #28a745;
            border-color: #28a745;
            color: #fff;
        }
        .openModalBtn:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #fff;
        }
    </style>

    <script>
        var items = <?php echo json_encode($items); ?>;

        // Display items dynamically
        function displayItems(itemsToDisplay) {
            const itemContainer = document.getElementById("itemContainer");
            itemContainer.innerHTML = ""; // Clear existing items

            itemsToDisplay.forEach(function (item) {
                const imageUrl = item.ImagePath
                    ? item.ImagePath
                    : `images/${item.name}.jpg`;

                const itemDiv = document.createElement("div");
                itemDiv.className = "col-md-6 mb-4";
                itemDiv.innerHTML = `
                    <div class="card card-flex">
                        <img src="${imageUrl}" class="card-img-left" alt="Item Image" width="150" height="auto">
                        <div class="card-body">
                            <h5 class="card-title">${item.name}</h5>
                            <p class="card-text">${item.description}</p>
                            <p class="card-text">Company: ${item.company}</p>
                            <p class="card-text">Price: $${parseFloat(item.price).toFixed(2)}</p>
                            <p class="card-text">Available: ${item.stock}</p>
                            <form method="post" action="items.php?sort=<?php echo $sortOrder; ?>">
                                <input type="hidden" name="item_id" value="${item.item_id}">
                                <button type="submit" class="btn openModalBtn">Add to Cart</button>
                            </form>
                        </div>
                    </div>
                `;
                itemContainer.appendChild(itemDiv);
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
            // Display items on page load
            displayItems(items);

            // Search functionality
            const searchInput = document.getElementById("searchItems");
            searchInput.addEventListener("input", function () {
                const searchTerm = searchInput.value.toLowerCase();
                const filteredItems = items.filter(item =>
                    item.name.toLowerCase().includes(searchTerm) ||
                    item.description.toLowerCase().includes(searchTerm)
                );
                displayItems(filteredItems);
            });
        });
    </script>
</head>
<body class="bg-light min-vh-100">
    <?php generate_header(); ?>
    <div class="container mt-5">
    <div class="container min-vw-75 min-vh-100 bg-white shadow-lg pt-3">
        <div class="container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" id="searchItems" class="form-control" placeholder="Search items...">
                </div>
                <div class="col-md-6">
                    <select id="sortSelect" class="form-select" onchange="window.location.href = '?sort=' + this.value;">
                        <option value="default" <?php echo ($sortOrder === 'default') ? 'selected' : ''; ?>>Sort By...</option>
                        <option value="price_low_high" <?php echo ($sortOrder === 'price_low_high') ? 'selected' : ''; ?>>Price Low to High</option>
                        <option value="price_high_low" <?php echo ($sortOrder === 'price_high_low') ? 'selected' : ''; ?>>Price High to Low</option>
                    </select>
                </div>
            </div>

            <div class="row" id="itemContainer">
                <!-- Items will be dynamically displayed here -->
            </div>
        </div>
    </div>

    <?php generate_footer(); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
