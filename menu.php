<?php
session_start();
include 'db.php';

// Handle Add to Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pizza_id'])) {
    $pizza_id = $_POST['pizza_id'];
    $quantity = $_POST['quantity'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    $_SESSION['cart'][$pizza_id] = isset($_SESSION['cart'][$pizza_id])
        ? $_SESSION['cart'][$pizza_id] + $quantity
        : $quantity;

    header("Location: menu.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pizza Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Adding jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Add aggressive cache busting -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">🍕 Pizza Paradise</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="menu.php">Menu</a></li>
                <li class="nav-item"><a class="nav-link" href="order.php">Order</a></li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item"><span class="nav-link">Welcome, <?= htmlspecialchars($_SESSION['user']) ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <!-- Pizza Menu -->
    <h2 class="text-center text-success mb-4">🌱 Veg Pizzas</h2>
    <div class="row g-4">
        <?php
        $veg = $conn->query("SELECT * FROM menu WHERE category = 'pizza' AND name IN ('Margherita', 'Paneer Tikka', 'Veggie Delight') ORDER BY display_order ASC");
        if ($veg && $veg->num_rows > 0) {
            while ($row = $veg->fetch_assoc()):
                // Add timestamp to image URL for cache busting
                $imageUrl = $row['image'] . '?v=' . time() . '&cb=' . rand();
        ?>
        <div class="col-md-4">
            <div class="card h-100 border-success">
                <img src="<?= $imageUrl ?>" class="card-img-top" alt="<?= $row['name'] ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title"><?= $row['name'] ?></h5>
                    <p class="card-text"><?= $row['description'] ?></p>
                    <p class="fw-bold">₹<?= number_format($row['price'], 2) ?></p>
                    <form method="POST">
                        <input type="hidden" name="pizza_id" value="<?= $row['id'] ?>">
                        <input type="number" name="quantity" value="1" min="1" class="form-control mb-2" max="20">
                        <button type="submit" class="btn btn-success w-100 add-to-cart-btn">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        } else {
            echo "<p class='text-center'>No veg pizzas available at the moment.</p>";
        }
        ?>
    </div>

    <h2 class="text-center text-danger mt-5 mb-4">🍗 Non-Veg Pizzas</h2>
    <div class="row g-4">
        <?php
        $nonveg = $conn->query("SELECT * FROM menu WHERE category = 'pizza' AND name IN ('BBQ Chicken', 'Pepperoni') ORDER BY display_order ASC");
        if ($nonveg && $nonveg->num_rows > 0) {
            while ($row = $nonveg->fetch_assoc()):
                // Add timestamp to image URL for cache busting
                $imageUrl = $row['image'] . '?v=' . time() . '&cb=' . rand();
        ?>
        <div class="col-md-4">
            <div class="card h-100 border-danger">
                <img src="<?= $imageUrl ?>" class="card-img-top" alt="<?= $row['name'] ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title"><?= $row['name'] ?></h5>
                    <p class="card-text"><?= $row['description'] ?></p>
                    <p class="fw-bold">₹<?= number_format($row['price'], 2) ?></p>
                    <form method="POST">
                        <input type="hidden" name="pizza_id" value="<?= $row['id'] ?>">
                        <input type="number" name="quantity" value="1" min="1" class="form-control mb-2" max="20">
                        <button type="submit" class="btn btn-danger w-100 add-to-cart-btn">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        } else {
            echo "<p class='text-center'>No non-veg pizzas available at the moment.</p>";
        }
        ?>
    </div>
    
    <!-- Beverages Section -->
    <h2 class="text-center text-info mt-5 mb-4">🥤 Beverages</h2>
    <div class="row g-4">
        <?php
        $beverages = $conn->query("SELECT * FROM menu WHERE category = 'beverage' ORDER BY display_order ASC");
        if ($beverages && $beverages->num_rows > 0) {
            while ($row = $beverages->fetch_assoc()):
                // Add timestamp to image URL for cache busting
                $imageUrl = $row['image'] . '?v=' . time() . '&cb=' . rand();
        ?>
        <div class="col-md-4">
            <div class="card h-100 border-info">
                <img src="<?= $imageUrl ?>" class="card-img-top" alt="<?= $row['name'] ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title"><?= $row['name'] ?></h5>
                    <p class="card-text"><?= $row['description'] ?></p>
                    <p class="fw-bold">₹<?= number_format($row['price'], 2) ?></p>
                    <form method="POST">
                        <input type="hidden" name="pizza_id" value="<?= $row['id'] ?>">
                        <input type="number" name="quantity" value="1" min="1" class="form-control mb-2" max="20">
                        <button type="submit" class="btn btn-info w-100 add-to-cart-btn">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        } else {
            echo "<p class='text-center'>No beverages available at the moment.</p>";
        }
        ?>
    </div>
    
    <!-- Sides Section -->
    <h2 class="text-center text-warning mt-5 mb-4">🍟 Sides</h2>
    <div class="row g-4">
        <?php
        $sides = $conn->query("SELECT * FROM menu WHERE category = 'side' ORDER BY display_order ASC");
        if ($sides && $sides->num_rows > 0) {
            while ($row = $sides->fetch_assoc()):
                // Add timestamp to image URL for cache busting
                $imageUrl = $row['image'] . '?v=' . time() . '&cb=' . rand();
        ?>
        <div class="col-md-4">
            <div class="card h-100 border-warning">
                <img src="<?= $imageUrl ?>" class="card-img-top" alt="<?= $row['name'] ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title"><?= $row['name'] ?></h5>
                    <p class="card-text"><?= $row['description'] ?></p>
                    <p class="fw-bold">₹<?= number_format($row['price'], 2) ?></p>
                    <form method="POST">
                        <input type="hidden" name="pizza_id" value="<?= $row['id'] ?>">
                        <input type="number" name="quantity" value="1" min="1" class="form-control mb-2" max="20">
                        <button type="submit" class="btn btn-warning w-100 add-to-cart-btn">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        } else {
            echo "<p class='text-center'>No sides available at the moment.</p>";
        }
        ?>
    </div>
    
    <!-- Dips Section -->
    <h2 class="text-center text-secondary mt-5 mb-4">🧂 Dips</h2>
    <div class="row g-4">
        <?php
        // First, get the special offer
        $specialDips = $conn->query("SELECT * FROM menu WHERE name = '3 Mini Dips'");
        if ($specialDips && $specialDips->num_rows > 0) {
            $row = $specialDips->fetch_assoc();
            // Add timestamp to image URL for cache busting
            $imageUrl = $row['image'] . '?v=' . time() . '&cb=' . rand();
        ?>
        <!-- Special Offer Card -->
        <div class="col-md-4">
            <div class="card h-100 border-success shadow-lg">
                <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-danger" style="font-size: 1rem;">
                    SPECIAL OFFER!
                </span>
                <img src="<?= $imageUrl ?>" class="card-img-top" alt="<?= $row['name'] ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title"><?= $row['name'] ?></h5>
                    <p class="card-text"><?= $row['description'] ?></p>
                    <p class="fw-bold text-success">
                        <span class="text-decoration-line-through text-muted">₹200</span>
                        <span class="ms-2">₹<?= number_format($row['price'], 2) ?></span>
                        <span class="badge bg-danger ms-2">25% OFF</span>
                    </p>
                    <form method="POST">
                        <input type="hidden" name="pizza_id" value="<?= $row['id'] ?>">
                        <input type="number" name="quantity" value="1" min="1" class="form-control mb-2" max="20">
                        <button type="submit" class="btn btn-success w-100 add-to-cart-btn">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
        <?php 
        }
        
        // Then get the regular dips
        $dips = $conn->query("SELECT * FROM menu WHERE category = 'dip' AND name != '3 Mini Dips' ORDER BY display_order ASC");
        if ($dips && $dips->num_rows > 0) {
            while ($row = $dips->fetch_assoc()):
                // Add timestamp to image URL for cache busting
                $imageUrl = $row['image'] . '?v=' . time() . '&cb=' . rand();
        ?>
        <div class="col-md-4">
            <div class="card h-100 border-secondary">
                <img src="<?= $imageUrl ?>" class="card-img-top" alt="<?= $row['name'] ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title"><?= $row['name'] ?></h5>
                    <p class="card-text"><?= $row['description'] ?></p>
                    <p class="fw-bold">₹<?= number_format($row['price'], 2) ?></p>
                    <form method="POST">
                        <input type="hidden" name="pizza_id" value="<?= $row['id'] ?>">
                        <input type="number" name="quantity" value="1" min="1" class="form-control mb-2" max="20">
                        <button type="submit" class="btn btn-secondary w-100 add-to-cart-btn">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        } else {
            echo "<p class='text-center'>No dips available at the moment.</p>";
        }
        ?>
    </div>
    
    <!-- Desserts Section -->
    <h2 class="text-center text-purple mt-5 mb-4">🍰 Desserts</h2>
    <div class="row g-4">
        <?php
        $desserts = $conn->query("SELECT * FROM menu WHERE category = 'dessert' ORDER BY display_order ASC");
        if ($desserts && $desserts->num_rows > 0) {
            while ($row = $desserts->fetch_assoc()):
                // Add timestamp to image URL for cache busting
                $imageUrl = $row['image'] . '?v=' . time() . '&cb=' . rand();
        ?>
        <div class="col-md-4">
            <div class="card h-100 border-purple">
                <img src="<?= $imageUrl ?>" class="card-img-top" alt="<?= $row['name'] ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title"><?= $row['name'] ?></h5>
                    <p class="card-text"><?= $row['description'] ?></p>
                    <p class="fw-bold">₹<?= number_format($row['price'], 2) ?></p>
                    <form method="POST">
                        <input type="hidden" name="pizza_id" value="<?= $row['id'] ?>">
                        <input type="number" name="quantity" value="1" min="1" class="form-control mb-2" max="20">
                        <button type="submit" class="btn btn-purple w-100 add-to-cart-btn">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        } else {
            echo "<p class='text-center'>No desserts available at the moment.</p>";
        }
        ?>
    </div>
     
    <div class="text-center mt-4">
        <a href="order.php" class="btn btn-dark">Go to Cart & Checkout</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript with jQuery -->
<script src="js/validate.js"></script>

<style>
    .text-purple {
        color: #6f42c1 !important;
    }
    
    .border-purple {
        border-color: #6f42c1 !important;
    }
    
    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: white;
    }
    
    .btn-purple:hover {
        background-color: #5a32a3;
        border-color: #5a32a3;
        color: white;
    }
</style>
</body>
</html>