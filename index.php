<?php
session_start();
include 'db.php';

// Handle Add to Cart from homepage
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pizza_id'])) {
    $pizza_id = $_POST['pizza_id'];
    $quantity = $_POST['quantity'];

    // Get the item details
    $result = $conn->query("SELECT * FROM menu WHERE id = $pizza_id");
    $item = $result->fetch_assoc();
    
    // Check if this is the special 3 Mini Dips offer
    $isDipsOffer = ($item['name'] == '3 Mini Dips');
    
    // Check if this is a combo offer
    $isComboOffer = ($item['category'] == 'combo');
    $isFamilyFeast = ($item['name'] == 'Family Feast');
    $isPizzaBeverageCombo = ($item['name'] == 'Pizza & Beverage Combo');
    $isPastryDuo = ($item['name'] == 'Pastry Duo');
    
    // Check if this is a bestseller pizza
    $isBestseller = false;
    $bestsellersResult = $conn->query("SELECT * FROM menu WHERE category = 'pizza' ORDER BY display_order DESC LIMIT 3");
    if ($bestsellersResult && $bestsellersResult->num_rows > 0) {
        while ($bestseller = $bestsellersResult->fetch_assoc()) {
            if ($bestseller['id'] == $pizza_id) {
                $isBestseller = true;
                break;
            }
        }
    }
    
    // Get today's special items (including combo offers)
    $specialItemsResult = $conn->query("SELECT * FROM menu WHERE name = '3 Mini Dips' UNION SELECT * FROM menu WHERE name != '3 Mini Dips' AND category = 'combo' ORDER BY display_order ASC LIMIT 5");
    $specialItemIds = array();
    if ($specialItemsResult && $specialItemsResult->num_rows > 0) {
        while ($row = $specialItemsResult->fetch_assoc()) {
            $specialItemIds[] = $row['id'];
        }
    }
    
    // Check if this item is specifically in today's special offers
    $isSpecialOffer = in_array($pizza_id, $specialItemIds);
    
    // Calculate the price to use (special price for offers)
    if ($isDipsOffer) {
        $price = 150; // Special price for 3 Mini Dips
    } else if ($isFamilyFeast) {
        $price = 1799; // Fixed price for Family Feast
    } else if ($isPizzaBeverageCombo) {
        $price = 699; // Fixed price for Pizza & Beverage Combo
    } else if ($isComboOffer || ($isSpecialOffer && $item['name'] != '3 Mini Dips')) {
        $price = $item['price'] * 0.8; // 20% discount for other special offers
    } else if ($isBestseller) {
        // Apply 10% discount to bestseller pizzas
        $price = $item['price'] * 0.9;
    } else {
        $price = $item['price']; // Regular price
    }
    
    // Handle customized combo offers
    if ($isFamilyFeast && isset($_POST['pizza1']) && isset($_POST['pizza2']) && isset($_POST['beverage1']) && isset($_POST['beverage2']) && isset($_POST['dessert']) && isset($_POST['side'])) {
        // Store customization details
        $customization = array(
            'pizza1' => $_POST['pizza1'],
            'pizza2' => $_POST['pizza2'],
            'beverage1' => $_POST['beverage1'],
            'beverage2' => $_POST['beverage2'],
            'dessert' => $_POST['dessert'],
            'side' => $_POST['side']
        );
        
        // Store the special price in the cart
        $cartKey = $pizza_id . "_special_" . time(); // Add timestamp to make it unique
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
        
        // Store special item info in session with customization
        if (!isset($_SESSION['special_items'])) {
            $_SESSION['special_items'] = array();
        }
        
        $_SESSION['special_items'][$cartKey] = array(
            'name' => $item['name'],
            'price' => $price,
            'original_price' => $isFamilyFeast ? 2200 : $item['price'], // Use ₹2200 as original price for Family Feast
            'customization' => $customization
        );
        
        $_SESSION['cart'][$cartKey] = isset($_SESSION['cart'][$cartKey])
            ? $_SESSION['cart'][$cartKey] + $quantity
            : $quantity;
    } else if ($isPizzaBeverageCombo && isset($_POST['pizza']) && isset($_POST['beverage'])) {
        // Store customization details for Pizza & Beverage Combo
        $customization = array(
            'pizza' => $_POST['pizza'],
            'beverage' => $_POST['beverage']
        );
        
        // Store the special price in the cart
        $cartKey = $pizza_id . "_special_" . time(); // Add timestamp to make it unique
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
        
        // Store special item info in session with customization
        if (!isset($_SESSION['special_items'])) {
            $_SESSION['special_items'] = array();
        }
        
        $_SESSION['special_items'][$cartKey] = array(
            'name' => $item['name'],
            'price' => $price,
            'original_price' => $isPizzaBeverageCombo ? 799 : $item['price'], // Use ₹799 as original price for Pizza & Beverage Combo
            'customization' => $customization
        );
        
        $_SESSION['cart'][$cartKey] = isset($_SESSION['cart'][$cartKey])
            ? $_SESSION['cart'][$cartKey] + $quantity
            : $quantity;
    } else if ($isPastryDuo && isset($_POST['pastry1']) && isset($_POST['pastry2'])) {
        // Store customization details for Pastry Duo
        $customization = array(
            'pastry1' => $_POST['pastry1'],
            'pastry2' => $_POST['pastry2']
        );
        
        // Store the special price in the cart
        $cartKey = $pizza_id . "_special_" . time(); // Add timestamp to make it unique
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
        
        // Store special item info in session with customization
        if (!isset($_SESSION['special_items'])) {
            $_SESSION['special_items'] = array();
        }
        
        $_SESSION['special_items'][$cartKey] = array(
            'name' => $item['name'],
            'price' => $price,
            'original_price' => $item['price'],
            'customization' => $customization
        );
        
        $_SESSION['cart'][$cartKey] = isset($_SESSION['cart'][$cartKey])
            ? $_SESSION['cart'][$cartKey] + $quantity
            : $quantity;
    } else if ($isSpecialOffer) {
        // Store the special price in the cart (for non-customized special offers)
        $cartKey = $pizza_id . "_special";
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
        
        // Store special item info in session
        if (!isset($_SESSION['special_items'])) {
            $_SESSION['special_items'] = array();
        }
        
        $_SESSION['special_items'][$pizza_id] = array(
            'name' => $item['name'],
            'price' => $price,
            'original_price' => $isDipsOffer ? 200 : ($isFamilyFeast ? 2200 : ($isPizzaBeverageCombo ? 799 : $item['price']))
        );
        
        $_SESSION['cart'][$cartKey] = isset($_SESSION['cart'][$cartKey])
            ? $_SESSION['cart'][$cartKey] + $quantity
            : $quantity;
    } else if ($isBestseller) {
        // Store the bestseller with discount in the cart
        $cartKey = $pizza_id . "_bestseller";
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
        
        // Store bestseller item info in session
        if (!isset($_SESSION['special_items'])) {
            $_SESSION['special_items'] = array();
        }
        
        $_SESSION['special_items'][$pizza_id] = array(
            'name' => $item['name'],
            'price' => $price,
            'original_price' => $item['price']
        );
        
        $_SESSION['cart'][$cartKey] = isset($_SESSION['cart'][$cartKey])
            ? $_SESSION['cart'][$cartKey] + $quantity
            : $quantity;
    } else {
        // Regular items
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
        
        $_SESSION['cart'][$pizza_id] = isset($_SESSION['cart'][$pizza_id])
            ? $_SESSION['cart'][$pizza_id] + $quantity
            : $quantity;
    }

    // Redirect back to index page with success message
    header("Location: index.php?added=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pizza Paradise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>"> <!-- Optional custom styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Adding jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Add cache busting -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-20px);}
            60% {transform: translateY(-10px);}
        }
        
        .offer-badge {
            animation: pulse 2s infinite;
            margin-top: 20px;
            margin-bottom: 20px;
            border-radius: 50px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .offer-badge:hover {
            animation: bounce 1s;
        }
        
        @media (min-width: 768px) {
            .offer-badge {
                position: fixed;
                top: 100px;
                right: 20px;
                z-index: 1000;
                max-width: 300px;
            }
        }
        
        @media (max-width: 767px) {
            .offer-badge {
                margin: 20px auto;
                display: block;
                width: 90%;
            }
        }
        
        /* Cart indicator */
        .cart-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">🍕 Pizza Restro</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
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

<!-- Special Offer Badge -->
<div class="offer-badge bg-warning text-dark p-3 rounded shadow">
    <div class="d-flex align-items-center">
        <div class="me-2">
            <i class="bi bi-lightning-fill fs-4"></i>
        </div>
        <div>
            <div class="fw-bold">🔥 SPECIAL OFFER! 🔥</div>
            <div>Get 3 Mini Dips for Just ₹150</div>
            <div class="small">Limited Time Only!</div>
        </div>
    </div>
</div>

<!-- Hero Section -->
<div class="container py-5">
    <div class="row align-items-center">
        <div class="col-md-6 text-center text-md-start">
            <h1 class="display-4 text-danger fw-bold">Fresh, Hot & Delicious</h1>
            <p class="lead">Order your favorite pizza in just a few clicks. Taste the love in every slice!</p>
            <a href="menu.php" class="btn btn-warning me-2">View Menu</a>
            <a href="order.php" class="btn btn-dark">Place Order</a>
        </div>
        <div class="col-md-6 text-center">
            <img src="assets/pizza4.jpg?v=<?= time() ?>" alt="Pizza" class="img-fluid rounded shadow">
        </div>
    </div>
</div>

<!-- Today's Specials -->
<div class="container py-5 bg-light rounded">
    <h2 class="text-center text-danger mb-4">🔥 Today's Special Offers</h2>
    <div class="row g-4">
        <?php
        // Get today's specials including the 3 Mini Dips offer and combo offers
        $specials = $conn->query("SELECT * FROM menu WHERE name = '3 Mini Dips' UNION SELECT * FROM menu WHERE name != '3 Mini Dips' AND category = 'combo' ORDER BY display_order ASC LIMIT 5");
        $specialItemIds = array();
        if ($specials && $specials->num_rows > 0) {
            while ($row = $specials->fetch_assoc()) {
                $specialItemIds[] = $row['id'];
            }
        }
        
        // Reset the query to display the items
        $specials = $conn->query("SELECT * FROM menu WHERE name = '3 Mini Dips' UNION SELECT * FROM menu WHERE name != '3 Mini Dips' AND category = 'combo' ORDER BY display_order ASC LIMIT 5");
        
        // Get items for customization options
        $pizzas = $conn->query("SELECT id, name FROM menu WHERE category = 'pizza' ORDER BY name");
        $beverages = $conn->query("SELECT id, name FROM menu WHERE category = 'beverage' ORDER BY name");
        $desserts = $conn->query("SELECT id, name FROM menu WHERE category = 'dessert' ORDER BY name");
        $pastries = $conn->query("SELECT id, name FROM menu WHERE category = 'dessert' ORDER BY name");
        
        if ($specials && $specials->num_rows > 0) {
            while ($row = $specials->fetch_assoc()):
                // Initialize variables to avoid undefined variable notices
                $isDipsOffer = false;
                $isComboOffer = false;
                $isFamilyFeast = false;
                $isPizzaBeverageCombo = false;
                $isPastryDuo = false;
                
                // Add timestamp to image URL for cache busting
                $imageUrl = $row['image'] . '?v=' . time();
                
                // Special styling for the 3 Mini Dips offer
                $isDipsOffer = ($row['name'] == '3 Mini Dips');
                $isComboOffer = ($row['category'] == 'combo');
                $isFamilyFeast = ($row['name'] == 'Family Feast');
                $isPizzaBeverageCombo = ($row['name'] == 'Pizza & Beverage Combo');
                $isPastryDuo = ($row['name'] == 'Pastry Duo');
                
                $cardClass = $isDipsOffer ? 'border-success shadow-lg' : ($isComboOffer ? 'border-primary' : 'border-danger');
                $badge = $isDipsOffer ? '<span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-danger">HOT DEAL!</span>' : ($isComboOffer ? '<span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-primary">COMBO!</span>' : '');
                
                // Check if this item is in special offers
                $isSpecialItem = in_array($row['id'], $specialItemIds);
                
                // Calculate special price
                $specialPrice = 0;
                if ($isDipsOffer) {
                    $specialPrice = 150;
                } else if ($isComboOffer || ($isSpecialItem && $row['name'] != '3 Mini Dips')) {
                    $specialPrice = $row['price'] * 0.8;
                }
        ?>
        <div class="col-md-4">
            <div class="card h-100 <?= $cardClass ?> position-relative">
                <?= $badge ?>
                <img src="<?= $imageUrl ?>" class="card-img-top" alt="<?= $row['name'] ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title"><?= $row['name'] ?></h5>
                    <p class="card-text"><?= $row['description'] ?></p>
                    <?php if ($isDipsOffer): ?>
                        <p class="fw-bold text-success">
                            <span class="text-decoration-line-through text-muted">₹200</span>
                            <span class="ms-2">₹<?= number_format(150, 2) ?></span>
                            <span class="badge bg-danger ms-2">25% OFF</span>
                        </p>
                    <?php elseif ($isFamilyFeast): ?>
                        <p class="fw-bold text-primary">
                            <span class="text-decoration-line-through text-muted">₹2200</span>
                            <span class="ms-2">₹<?= number_format(1799, 2) ?></span>
                            <span class="badge bg-primary ms-2">18% OFF</span>
                        </p>
                    <?php elseif ($isPizzaBeverageCombo): ?>
                        <p class="fw-bold text-primary">
                            <span class="text-decoration-line-through text-muted">₹799</span>
                            <span class="ms-2">₹<?= number_format(699, 2) ?></span>
                            <span class="badge bg-primary ms-2">13% OFF</span>
                        </p>
                    <?php else: ?>
                        <p class="fw-bold <?= $isComboOffer ? 'text-primary' : 'text-danger' ?>">
                            <span class="text-decoration-line-through text-muted">₹<?= number_format($row['price'], 2) ?></span>
                            <span class="ms-2">₹<?= number_format($specialPrice, 2) ?></span>
                            <span class="badge <?= $isComboOffer ? 'bg-primary' : 'bg-danger' ?> ms-2"><?= $isComboOffer ? '20% OFF' : '20% OFF' ?></span>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Add to Cart Form -->
                    <form method="POST" class="mt-2">
                        <input type="hidden" name="pizza_id" value="<?= $row['id'] ?>">
                        
                        <?php if ($isFamilyFeast): ?>
                            <!-- Family Feast Customization -->
                            <div class="mb-2">
                                <label class="form-label"><small>Choose 2 Pizzas:</small></label>
                                <select name="pizza1" class="form-select form-select-sm mb-1" required>
                                    <option value="">Select first pizza</option>
                                    <?php 
                                    if ($pizzas && $pizzas->num_rows > 0) {
                                        $pizzas->data_seek(0); // Reset pointer
                                        while ($pizza = $pizzas->fetch_assoc()) {
                                            echo "<option value='".$pizza['id']."'>".$pizza['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <select name="pizza2" class="form-select form-select-sm" required>
                                    <option value="">Select second pizza</option>
                                    <?php 
                                    if ($pizzas && $pizzas->num_rows > 0) {
                                        $pizzas->data_seek(0); // Reset pointer
                                        while ($pizza = $pizzas->fetch_assoc()) {
                                            echo "<option value='".$pizza['id']."'>".$pizza['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label"><small>Choose 2 Beverages:</small></label>
                                <select name="beverage1" class="form-select form-select-sm mb-1" required>
                                    <option value="">Select first beverage</option>
                                    <?php 
                                    if ($beverages && $beverages->num_rows > 0) {
                                        $beverages->data_seek(0); // Reset pointer
                                        while ($beverage = $beverages->fetch_assoc()) {
                                            echo "<option value='".$beverage['id']."'>".$beverage['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <select name="beverage2" class="form-select form-select-sm" required>
                                    <option value="">Select second beverage</option>
                                    <?php 
                                    if ($beverages && $beverages->num_rows > 0) {
                                        $beverages->data_seek(0); // Reset pointer
                                        while ($beverage = $beverages->fetch_assoc()) {
                                            echo "<option value='".$beverage['id']."'>".$beverage['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label"><small>Choose 1 Dessert:</small></label>
                                <select name="dessert" class="form-select form-select-sm mb-1" required>
                                    <option value="">Select dessert</option>
                                    <?php 
                                    if ($desserts && $desserts->num_rows > 0) {
                                        $desserts->data_seek(0); // Reset pointer
                                        while ($dessert = $desserts->fetch_assoc()) {
                                            echo "<option value='".$dessert['id']."'>".$dessert['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label"><small>Choose 1 Side:</small></label>
                                <select name="side" class="form-select form-select-sm" required>
                                    <option value="">Select side</option>
                                    <?php 
                                    $sides = $conn->query("SELECT id, name FROM menu WHERE category = 'side' ORDER BY name");
                                    if ($sides && $sides->num_rows > 0) {
                                        while ($side = $sides->fetch_assoc()) {
                                            echo "<option value='".$side['id']."'>".$side['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php elseif ($isPizzaBeverageCombo): ?>
                            <!-- Pizza & Beverage Combo Customization -->
                            <div class="mb-2">
                                <label class="form-label"><small>Choose 1 Pizza:</small></label>
                                <select name="pizza" class="form-select form-select-sm mb-1" required>
                                    <option value="">Select pizza</option>
                                    <?php 
                                    if ($pizzas && $pizzas->num_rows > 0) {
                                        $pizzas->data_seek(0); // Reset pointer
                                        while ($pizza = $pizzas->fetch_assoc()) {
                                            echo "<option value='".$pizza['id']."'>".$pizza['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label"><small>Choose 1 Beverage:</small></label>
                                <select name="beverage" class="form-select form-select-sm" required>
                                    <option value="">Select beverage</option>
                                    <?php 
                                    if ($beverages && $beverages->num_rows > 0) {
                                        $beverages->data_seek(0); // Reset pointer
                                        while ($beverage = $beverages->fetch_assoc()) {
                                            echo "<option value='".$beverage['id']."'>".$beverage['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php elseif ($isPastryDuo): ?>
                            <!-- Pastry Duo Customization -->
                            <div class="mb-2">
                                <label class="form-label"><small>Choose 2 Pastries:</small></label>
                                <select name="pastry1" class="form-select form-select-sm mb-1" required>
                                    <option value="">Select first pastry</option>
                                    <?php 
                                    if ($pastries && $pastries->num_rows > 0) {
                                        $pastries->data_seek(0); // Reset pointer
                                        while ($pastry = $pastries->fetch_assoc()) {
                                            echo "<option value='".$pastry['id']."'>".$pastry['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <select name="pastry2" class="form-select form-select-sm" required>
                                    <option value="">Select second pastry</option>
                                    <?php 
                                    if ($pastries && $pastries->num_rows > 0) {
                                        $pastries->data_seek(0); // Reset pointer
                                        while ($pastry = $pastries->fetch_assoc()) {
                                            echo "<option value='".$pastry['id']."'>".$pastry['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="input-group">
                            <input type="number" name="quantity" value="1" min="1" class="form-control" style="max-width: 80px;">
                            <button type="submit" class="btn <?= $isDipsOffer ? 'btn-success' : ($isComboOffer ? 'btn-primary' : 'btn-danger') ?>">Add to Cart</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        } else {
            echo "<p class='text-center'>No special offers available at the moment.</p>";
        }
        ?>
    </div>
</div>

<!-- Bestsellers -->
<div class="container py-5">
    <h2 class="text-center text-warning mb-4">⭐ Bestseller Pizzas</h2>
    <div class="row g-4">
        <?php
        // Get bestsellers (last 3 items)
        $bestsellers = $conn->query("SELECT * FROM menu WHERE category = 'pizza' ORDER BY display_order DESC LIMIT 3");
        if ($bestsellers && $bestsellers->num_rows > 0) {
            while ($row = $bestsellers->fetch_assoc()):
                // Add timestamp to image URL for cache busting
                $imageUrl = $row['image'] . '?v=' . time();
                
                // Get discounted price (10% off)
                $discountedPrice = $row['price'] * 0.9;
        ?>
        <div class="col-md-4">
            <div class="card h-100 border-warning">
                <img src="<?= $imageUrl ?>" class="card-img-top" alt="<?= $row['name'] ?>" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title"><?= $row['name'] ?></h5>
                    <p class="card-text"><?= $row['description'] ?></p>
                    <p class="fw-bold">
                        <span class="text-decoration-line-through text-muted">₹<?= number_format($row['price'], 2) ?></span>
                        <span class="ms-2">₹<?= number_format($discountedPrice, 2) ?></span>
                        <span class="badge bg-warning text-dark ms-2">10% OFF</span>
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-warning text-dark">Bestseller</span>
                        <!-- Add to Cart Form -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="pizza_id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-warning btn-sm">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        } else {
            echo "<p class='text-center'>No bestsellers available at the moment.</p>";
        }
        ?>
    </div>
</div>

<!-- Features Section -->
<div class="container py-5">
    <h2 class="text-center text-danger mb-4">Why Choose Us?</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h5 class="card-title">🍅 Fresh Ingredients</h5>
                    <p class="card-text">We use only the freshest veggies, meats, and cheeses for every pizza.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h5 class="card-title">🚀 Fast Delivery</h5>
                    <p class="card-text">Hot pizza delivered to your doorstep in under 30 minutes.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h5 class="card-title">💖 Loved Locally</h5>
                    <p class="card-text">Rated #1 pizza spot in town by our amazing customers.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cart Indicator -->
<?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
<div class="cart-indicator">
    <a href="order.php" class="btn btn-success btn-lg position-relative">
        <i class="bi bi-cart"></i> View Cart
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= array_sum($_SESSION['cart']) ?>
        </span>
    </a>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<footer class="bg-dark text-white mt-5 py-4">
    <div class="container text-center">
        <h5 class="mb-3">🍕 Pizza Restro</h5>
        <p>25, Senapati Bapat Road, ShivajiNagar ,Pune</p>
        <p>Email: contact@pizzarestro.com | Phone: +91 98765 43210</p>
        <div class="mb-2">
            <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i> Facebook</a>
            <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i> Instagram</a>
            <a href="#" class="text-white"><i class="bi bi-twitter"></i> Twitter</a>
        </div>
        <p class="mb-0">&copy; 2025 Pizza Restro. All rights reserved.</p>
    </div>
</footer>

<!-- Success message -->
<?php if (isset($_GET['added']) && $_GET['added'] == 1): ?>
<script>
    alert("Item added to cart successfully!");
</script>
<?php endif; ?>

</body>
</html>