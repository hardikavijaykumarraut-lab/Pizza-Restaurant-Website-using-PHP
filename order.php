<?php
session_start();
include 'db.php';

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
$success = "";
$billGenerated = false;

// Define delivery charges based on area
$deliveryCharges = array(
    'shivajinagar' => 20,
    'aundh' => 35,
    'kothrud' => 45,
    'karve nagar' => 40,
    'fc road' => 25
);

// Handle Remove Item from Cart
if (isset($_GET['remove'])) {
    $itemKey = $_GET['remove'];
    if (isset($_SESSION['cart'][$itemKey])) {
        unset($_SESSION['cart'][$itemKey]);
        // Also remove from special items if it's a special item
        if (isset($_SESSION['special_items'])) {
            $actualId = str_replace('_special', '', $itemKey);
            $actualId = preg_replace('/_special_\d+/', '', $actualId); // Handle customized items
            if (isset($_SESSION['special_items'][$itemKey])) {
                unset($_SESSION['special_items'][$itemKey]);
            } else if (isset($_SESSION['special_items'][$actualId])) {
                unset($_SESSION['special_items'][$actualId]);
            }
        }
    }
    header("Location: order.php");
    exit;
}

// Handle order cancellation
if (isset($_GET['cancel_order']) && isset($_SESSION['order_time']) && isset($_SESSION['order_bill_id'])) {
    $orderTime = $_SESSION['order_time'];
    $currentTime = time();
    $timeDiff = $currentTime - $orderTime;
    
    // Check if within 10 minutes (600 seconds)
    if ($timeDiff <= 600) {
        // Cancel all items in the order in the database
        $orderBillId = $_SESSION['order_bill_id'];
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_bill_id = ?");
        if ($stmt) {
            $stmt->bind_param("s", $orderBillId);
            $stmt->execute();
        }
        
        // Clear session data
        unset($_SESSION['customer_name']);
        unset($_SESSION['customer_address']);
        unset($_SESSION['payment_method']);
        unset($_SESSION['delivery_time']);
        unset($_SESSION['order_items']);
        unset($_SESSION['special_items']);
        unset($_SESSION['order_time']);
        unset($_SESSION['order_id']);
        unset($_SESSION['delivery_area']);
        $success = "✅ Your order has been successfully cancelled!";
    } else {
        $success = "❌ Sorry, cancellation is only possible within 10 minutes of order placement.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['customer_name']) && isset($_POST['customer_address'])) {
        // Save customer info for bill generation
        $_SESSION['customer_name'] = $conn->real_escape_string($_POST['customer_name']);
        $_SESSION['customer_address'] = $conn->real_escape_string($_POST['customer_address']);
        $_SESSION['payment_method'] = $conn->real_escape_string($_POST['payment_method']);
        $_SESSION['delivery_area'] = $conn->real_escape_string($_POST['delivery_area']);
        
        // Generate unique order bill ID if not already generated
        if (!isset($_SESSION['order_bill_id'])) {
            $orderBillId = 'ORD-' . strtoupper(substr(md5(time() . rand()), 0, 8));
            $_SESSION['order_bill_id'] = $orderBillId;
        }
        
        // Set delivery time based on payment method
        switch ($_SESSION['payment_method']) {
            case 'cod':
                $deliveryTime = "30-40 minutes";
                break;
            case 'upi':
                $deliveryTime = "25-35 minutes";
                break;
            case 'card':
                $deliveryTime = "20-30 minutes";
                break;
            default:
                $deliveryTime = "30-40 minutes";
        }
        
        $_SESSION['delivery_time'] = $deliveryTime;
        $billGenerated = true;
    } elseif (isset($_POST['confirm_order'])) {
        // Finalize order
        $name = $_SESSION['customer_name'];
        $address = $_SESSION['customer_address'];
        $payment = $_SESSION['payment_method'];
        
        // Generate unique order bill ID if not already generated
        if (!isset($_SESSION['order_bill_id'])) {
            $orderBillId = 'ORD-' . strtoupper(substr(md5(time() . rand()), 0, 8));
            $_SESSION['order_bill_id'] = $orderBillId;
        } else {
            $orderBillId = $_SESSION['order_bill_id'];
        }
        
        // Store cart items for confirmation display
        $_SESSION['order_items'] = array();
        $orderIds = array(); // To store order IDs for cancellation
        
        foreach ($cart as $cartKey => $qty) {
            // Check if this is a special item
            if (strpos($cartKey, '_special') !== false || strpos($cartKey, '_bestseller') !== false) {
                // Check if this is a customized item
                if (isset($_SESSION['special_items'][$cartKey])) {
                    // Customized special item
                    $item = $_SESSION['special_items'][$cartKey];
                    $_SESSION['order_items'][] = array(
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'original_price' => $item['original_price'],
                        'quantity' => $qty,
                        'is_special' => true,
                        'customization' => isset($item['customization']) ? $item['customization'] : null
                    );
                    
                    // Extract the actual item ID for database storage
                    $parts = explode('_', $cartKey);
                    $pizza_id = $parts[0];
                } else {
                    // Non-customized special item (including bestsellers)
                    $pizza_id = str_replace('_special', '', $cartKey);
                    $pizza_id = str_replace('_bestseller', '', $pizza_id);
                    
                    // Get special item info
                    if (isset($_SESSION['special_items'][$pizza_id])) {
                        $item = $_SESSION['special_items'][$pizza_id];
                        $_SESSION['order_items'][] = array(
                            'name' => $item['name'],
                            'price' => $item['price'],
                            'original_price' => $item['original_price'],
                            'quantity' => $qty,
                            'is_special' => true
                        );
                    } else {
                        // If not in special_items, fetch from database (for bestsellers)
                        $result = $conn->query("SELECT * FROM menu WHERE id = $pizza_id");
                        if ($result && $result->num_rows > 0) {
                            $pizza = $result->fetch_assoc();
                            $_SESSION['order_items'][] = array(
                                'name' => $pizza['name'],
                                'price' => $pizza['price'],
                                'quantity' => $qty,
                                'is_special' => (strpos($cartKey, '_bestseller') !== false)
                            );
                        } else {
                            // Fallback if query fails
                            $_SESSION['order_items'][] = array(
                                'name' => "Unknown Item",
                                'price' => 0,
                                'quantity' => $qty,
                                'is_special' => (strpos($cartKey, '_bestseller') !== false)
                            );
                        }
                    }
                }
            } else {
                // Regular item
                $pizza_id = $cartKey;
                $result = $conn->query("SELECT * FROM menu WHERE id = $pizza_id");
                if ($result && $result->num_rows > 0) {
                    $pizza = $result->fetch_assoc();
                    $_SESSION['order_items'][] = array(
                        'name' => $pizza['name'],
                        'price' => $pizza['price'],
                        'quantity' => $qty,
                        'is_special' => false
                    );
                } else {
                    // Fallback if query fails
                    $_SESSION['order_items'][] = array(
                        'name' => "Unknown Item",
                        'price' => 0,
                        'quantity' => $qty,
                        'is_special' => false
                    );
                }
            }
            
            $insertResult = $conn->query("INSERT INTO orders (order_bill_id, customer_name, customer_address, pizza_id, quantity) VALUES ('$orderBillId', '$name', '$address', $pizza_id, $qty)");
            if ($insertResult) {
                $orderIds[] = $conn->insert_id;
            }
        }
        
        // Store order time and order bill ID for cancellation
        $_SESSION['order_time'] = time();
        $_SESSION['order_bill_id'] = $orderBillId; // Store the order bill ID for cancellation
        
        // Clear cart but keep special items info for confirmation
        $_SESSION['cart'] = array();
        $success = "🎉 Your order has been placed successfully!";
    } elseif (isset($_POST['new_order'])) {
        // Clear session data when starting a new order
        unset($_SESSION['customer_name']);
        unset($_SESSION['customer_address']);
        unset($_SESSION['payment_method']);
        unset($_SESSION['delivery_time']);
        unset($_SESSION['order_items']);
        unset($_SESSION['special_items']);
        unset($_SESSION['order_time']);
        unset($_SESSION['order_id']);
        unset($_SESSION['delivery_area']);
        unset($_SESSION['order_bill_id']); // Clear order bill ID
        header("Location: menu.php");
        exit;
    }
}

// Check if we have customer info in session (for displaying bill after confirmation)
$showConfirmation = !empty($success) && isset($_SESSION['customer_name']) && !isset($_GET['cancel_order']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Adding jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php if (!empty($success) && !isset($_POST['confirm_order']) && !isset($_GET['cancel_order'])): ?>
    <meta http-equiv="refresh" content="5;url=index.php">
    <?php endif; ?>
    <!-- Add cache busting -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body>
<div class="container py-4">
    <h2 class="text-center text-danger mb-4">Your Cart</h2>

    <?php if ($showConfirmation): ?>
        <!-- Order Confirmation with Bill -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white text-center">
                        <h4 class="mb-0">ORDER CONFIRMED</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h5>🎉 Thank you for your order!</h5>
                            <p>Your order has been successfully placed.</p>
                            
                            <!-- Timer for cancellation -->
                            <?php if (isset($_SESSION['order_time'])): 
                                $orderTime = $_SESSION['order_time'];
                                $currentTime = time();
                                $timeDiff = $currentTime - $orderTime;
                                $timeLeft = 600 - $timeDiff; // 10 minutes in seconds
                                if ($timeLeft > 0):
                            ?>
                            <div class="alert alert-warning">
                                <strong>⏰ Cancellation Window:</strong> 
                                <span id="timer">You have <?= gmdate("i:s", $timeLeft) ?> left to cancel this order</span>
                                <br><br>
                                <a href="order.php?cancel_order=1" class="btn btn-danger btn-sm">Cancel Order</a>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <strong>ℹ️ Note:</strong> The 10-minute cancellation window has expired.
                            </div>
                            <?php endif; endif; ?>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <strong>Customer:</strong> <?= htmlspecialchars($_SESSION['customer_name']) ?><br>
                                <strong>Address:</strong> <?= htmlspecialchars($_SESSION['customer_address']) ?><br>
                                <strong>Delivery Area:</strong> <?= htmlspecialchars($_SESSION['delivery_area']) ?>
                            </div>
                            <div class="col-6 text-end">
                                <strong>Order Bill ID:</strong> <?= isset($_SESSION['order_bill_id']) ? htmlspecialchars($_SESSION['order_bill_id']) : 'N/A' ?><br>
                                <strong>Payment Method:</strong> 
                                <?php 
                                switch ($_SESSION['payment_method']) {
                                    case 'cod': echo 'Cash on Delivery'; break;
                                    case 'upi': echo 'UPI Payment'; break;
                                    case 'card': echo 'Credit/Debit Card'; break;
                                    default: echo 'Not specified';
                                }
                                ?><br>
                                <strong>Order Time:</strong> <?= date('d M Y, H:i', $_SESSION['order_time']) ?>
                            </div>
                        </div>
                        
                        <h5 class="mb-3">Order Details</h5>
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Image</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $grandTotal = 0;
                                if (isset($_SESSION['order_items'])) {
                                    foreach ($_SESSION['order_items'] as $item):
                                        $total = $item['price'] * $item['quantity'];
                                        $grandTotal += $total;
                                        // Get image for the item
                                        $imageResult = $conn->query("SELECT image FROM menu WHERE name = '" . $conn->real_escape_string($item['name']) . "'");
                                        $imageRow = $imageResult->fetch_assoc();
                                        $imageUrl = isset($imageRow['image']) ? $imageRow['image'] . '?v=' . time() : '';
                                ?>
                                <tr>
                                    <td>
                                        <?= $item['name'] ?>
                                        <?php if (isset($item['is_special']) && $item['is_special']): ?>
                                            <span class="badge bg-success">Special Offer</span>
                                        <?php endif; ?>
                                        <?php if (isset($item['customization'])): ?>
                                            <br><small class="text-muted">
                                                <?php 
                                                $customization = $item['customization'];
                                                if (isset($customization['pizza1'])) {
                                                    // Family Feast customization
                                                    $pizza1Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pizza1']);
                                                    $pizza1 = $pizza1Result->fetch_assoc();
                                                    $pizza2Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pizza2']);
                                                    $pizza2 = $pizza2Result->fetch_assoc();
                                                    $beverage1Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['beverage1']);
                                                    $beverage1 = $beverage1Result->fetch_assoc();
                                                    $beverage2Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['beverage2']);
                                                    $beverage2 = $beverage2Result->fetch_assoc();
                                                    $dessertResult = $conn->query("SELECT name FROM menu WHERE id = " . $customization['dessert']);
                                                    $dessert = $dessertResult->fetch_assoc();
                                                    $sideResult = $conn->query("SELECT name FROM menu WHERE id = " . $customization['side']);
                                                    $side = $sideResult->fetch_assoc();
                                                    echo "Custom: " . $pizza1['name'] . ", " . $pizza2['name'] . ", " . $beverage1['name'] . ", " . $beverage2['name'] . ", " . $dessert['name'] . ", " . $side['name'];
                                                } else if (isset($customization['pizza'])) {
                                                    // Pizza & Beverage Combo customization
                                                    $pizzaResult = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pizza']);
                                                    $pizza = $pizzaResult->fetch_assoc();
                                                    $beverageResult = $conn->query("SELECT name FROM menu WHERE id = " . $customization['beverage']);
                                                    $beverage = $beverageResult->fetch_assoc();
                                                    echo "Custom: " . $pizza['name'] . ", " . $beverage['name'];
                                                } else if (isset($customization['pastry1'])) {
                                                    // Pastry Duo customization
                                                    $pastry1Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pastry1']);
                                                    $pastry1 = $pastry1Result->fetch_assoc();
                                                    $pastry2Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pastry2']);
                                                    $pastry2 = $pastry2Result->fetch_assoc();
                                                    echo "Custom: " . $pastry1['name'] . ", " . $pastry2['name'];
                                                }
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($imageUrl): ?>
                                            <img src="<?= $imageUrl ?>" alt="<?= $item['name'] ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>
                                        ₹<?= number_format($item['price'], 2) ?>
                                        <?php if (isset($item['is_special']) && $item['is_special'] && isset($item['original_price'])): ?>
                                            <br><span class="text-muted text-decoration-line-through">
                                                <?php 
                                                // Show specific strikethrough prices for certain items
                                                if ($item['name'] == 'Family Feast') {
                                                    echo '₹2200';
                                                } else if ($item['name'] == 'Pizza & Beverage Combo') {
                                                    echo '₹799';
                                                } else if ($item['name'] == '3 Mini Dips') {
                                                    echo '₹200';
                                                } else {
                                                    echo '₹' . number_format($item['original_price'], 2);
                                                }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>₹<?= number_format($total, 2) ?></td>
                                </tr>
                                <?php 
                                    endforeach;
                                }
                                ?>
                            </tbody>
                            <tfoot class="table-light">
                                <?php
                                // Calculate delivery charges
                                $deliveryCharge = 0;
                                if (isset($_SESSION['delivery_area']) && isset($deliveryCharges[$_SESSION['delivery_area']])) {
                                    $deliveryCharge = $deliveryCharges[$_SESSION['delivery_area']];
                                    // Add delivery charge to grand total
                                    $grandTotal += $deliveryCharge;
                                }
                                ?>
                                <tr>
                                    <th colspan="4" class="text-end">Subtotal</th>
                                    <th>₹<?= number_format($grandTotal - $deliveryCharge, 2) ?></th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Delivery Charges</th>
                                    <th>₹<?= number_format($deliveryCharge, 2) ?></th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Grand Total</th>
                                    <th>₹<?= number_format($grandTotal, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <div class="alert alert-info">
                            <strong>Estimated Delivery Time:</strong> <?= $_SESSION['delivery_time'] ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="index.php" class="btn btn-primary">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (!empty($success) && isset($_GET['cancel_order'])): ?>
        <div class="alert alert-info text-center">
            <?= $success ?><br>
            <a href="index.php" class="btn btn-primary mt-3">Return to Home</a>
        </div>
    <?php elseif (!empty($success)): ?>
        <div class="alert alert-success text-center">
            <?= $success ?><br>
            <a href="index.php" class="btn btn-primary mt-3">Return to Home</a>
        </div>
    <?php elseif (empty($cart)): ?>
        <p class="text-center">Your cart is empty. <a href="menu.php">Go back to menu</a></p>
    <?php else: ?>
        <?php if (!$billGenerated): ?>
            <!-- Customer Info Form -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Customer Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="order-form">
                                <div class="mb-3">
                                    <label for="customer_name" class="form-label">Full Name</label>
                                    <input type="text" name="customer_name" id="customer_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="customer_address" class="form-label">Delivery Address</label>
                                    <textarea name="customer_address" id="customer_address" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="delivery_area" class="form-label">Delivery Area</label>
                                    <select name="delivery_area" id="delivery_area" class="form-select" required>
                                        <option value="">Select Delivery Area</option>
                                        <option value="shivajinagar">Shivajinagar (₹20)</option>
                                        <option value="aundh">Aundh (₹35)</option>
                                        <option value="kothrud">Kothrud (₹45)</option>
                                        <option value="karve nagar">Karve Nagar (₹40)</option>
                                        <option value="fc road">FC Road (₹25)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <select name="payment_method" id="payment_method" class="form-select" required>
                                        <option value="">Select Payment Method</option>
                                        <option value="cod">Cash on Delivery (COD)</option>
                                        <option value="upi">UPI Payment</option>
                                        <option value="card">Credit/Debit Card</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100">Generate Bill</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cart Items -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <h4 class="mb-3">Order Summary 
                        <a href="menu.php" class="btn btn-sm btn-outline-primary float-end">Add More Items</a>
                    </h4>
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Item</th>
                                <th>Image</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $grandTotal = 0;
                            foreach ($cart as $cartKey => $qty):
                                // Check if this is a special item
                                if (strpos($cartKey, '_special') !== false || strpos($cartKey, '_bestseller') !== false) {
                                    // Get special item info
                                    if (isset($_SESSION['special_items'][$cartKey])) {
                                        // Customized item
                                        $item = $_SESSION['special_items'][$cartKey];
                                        $price = $item['price'];
                                        $name = $item['name'];
                                        $originalPrice = $item['original_price'];
                                        $customization = isset($item['customization']) ? $item['customization'] : null;
                                        
                                        // Get image (use the combo offer image)
                                        $parts = explode('_', $cartKey);
                                        $pizza_id = $parts[0]; // Extract ID from key
                                        $imageResult = $conn->query("SELECT image FROM menu WHERE id = $pizza_id");
                                        $imageRow = $imageResult->fetch_assoc();
                                        $imageUrl = isset($imageRow['image']) ? $imageRow['image'] . '?v=' . time() : '';
                                        
                                        $total = $price * $qty;
                                        $grandTotal += $total;
                                    } else {
                                        // Non-customized special item (including bestsellers)
                                        $pizza_id = str_replace('_special', '', $cartKey);
                                        $pizza_id = str_replace('_bestseller', '', $pizza_id);
                                        
                                        // Get special item info
                                        if (isset($_SESSION['special_items'][$pizza_id])) {
                                            $item = $_SESSION['special_items'][$pizza_id];
                                            $price = $item['price'];
                                            $name = $item['name'];
                                            $originalPrice = isset($item['original_price']) ? $item['original_price'] : $price;
                                            $customization = null;
                                            
                                            // Get image
                                            $imageResult = $conn->query("SELECT image FROM menu WHERE id = $pizza_id");
                                            $imageRow = $imageResult && $imageResult->num_rows > 0 ? $imageResult->fetch_assoc() : null;
                                            $imageUrl = isset($imageRow['image']) ? $imageRow['image'] . '?v=' . time() : '';
                                            
                                            $total = $price * $qty;
                                            $grandTotal += $total;
                                        } else {
                                            // Fallback to regular item for bestsellers
                                            $result = $conn->query("SELECT * FROM menu WHERE id = $pizza_id");
                                            if ($result && $result->num_rows > 0) {
                                                $pizza = $result->fetch_assoc();
                                                $price = $pizza['price'];
                                                $name = $pizza['name'];
                                                $originalPrice = $price; // No discount info available
                                                $customization = null;
                                                
                                                // Get image
                                                $imageUrl = isset($pizza['image']) ? $pizza['image'] . '?v=' . time() : '';
                                                
                                                $total = $price * $qty;
                                                $grandTotal += $total;
                                            } else {
                                                // Fallback values if query fails
                                                $price = 0;
                                                $name = "Unknown Item";
                                                $originalPrice = 0;
                                                $customization = null;
                                                $imageUrl = '';
                                                $total = 0;
                                            }
                                        }
                                    }
                                } else {
                                    // Regular item
                                    $pizza_id = $cartKey;
                                    $result = $conn->query("SELECT * FROM menu WHERE id = $pizza_id");
                                    if ($result && $result->num_rows > 0) {
                                        $pizza = $result->fetch_assoc();
                                        $price = $pizza['price'];
                                        $name = $pizza['name'];
                                    } else {
                                        // Fallback values if query fails
                                        $price = 0;
                                        $name = "Unknown Item";
                                    }
                                    $customization = null;
                                    
                                    // Get image
                                    $imageUrl = isset($pizza['image']) ? $pizza['image'] . '?v=' . time() : '';
                                    
                                    $total = $price * $qty;
                                    $grandTotal += $total;
                                }
                            ?>
                            <tr>
                                <td>
                                    <?= $name ?>
                                    <?php if (strpos($cartKey, '_special') !== false): ?>
                                        <span class="badge bg-success">Special Offer</span>
                                    <?php endif; ?>
                                    <?php if ($customization): ?>
                                        <br><small class="text-muted">
                                            <?php 
                                            if (isset($customization['pizza1'])) {
                                                // Family Feast customization
                                                $pizza1Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pizza1']);
                                                // FIXED: Added error checking before calling fetch_assoc()
                                                if ($pizza1Result && $pizza1Result->num_rows > 0) {
                                                    $pizza1 = $pizza1Result->fetch_assoc();
                                                } else {
                                                    $pizza1 = array('name' => 'Unknown Pizza');
                                                }
                                                $pizza2Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pizza2']);
                                                // FIXED: Added error checking before calling fetch_assoc()
                                                if ($pizza2Result && $pizza2Result->num_rows > 0) {
                                                    $pizza2 = $pizza2Result->fetch_assoc();
                                                } else {
                                                    $pizza2 = array('name' => 'Unknown Pizza');
                                                }
                                                $beverage1Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['beverage1']);
                                                // FIXED: Added error checking before calling fetch_assoc()
                                                if ($beverage1Result && $beverage1Result->num_rows > 0) {
                                                    $beverage1 = $beverage1Result->fetch_assoc();
                                                } else {
                                                    $beverage1 = array('name' => 'Unknown Beverage');
                                                }
                                                $beverage2Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['beverage2']);
                                                // FIXED: Added error checking before calling fetch_assoc()
                                                if ($beverage2Result && $beverage2Result->num_rows > 0) {
                                                    $beverage2 = $beverage2Result->fetch_assoc();
                                                } else {
                                                    $beverage2 = array('name' => 'Unknown Beverage');
                                                }
                                                $dessertResult = $conn->query("SELECT name FROM menu WHERE id = " . $customization['dessert']);
                                                // FIXED: Added error checking before calling fetch_assoc()
                                                if ($dessertResult && $dessertResult->num_rows > 0) {
                                                    $dessert = $dessertResult->fetch_assoc();
                                                } else {
                                                    $dessert = array('name' => 'Unknown Dessert');
                                                }
                                                $sideResult = $conn->query("SELECT name FROM menu WHERE id = " . $customization['side']);
                                                // FIXED: Added error checking before calling fetch_assoc()
                                                if ($sideResult && $sideResult->num_rows > 0) {
                                                    $side = $sideResult->fetch_assoc();
                                                } else {
                                                    $side = array('name' => 'Unknown Side');
                                                }
                                                echo "Custom: " . $pizza1['name'] . ", " . $pizza2['name'] . ", " . $beverage1['name'] . ", " . $beverage2['name'] . ", " . $dessert['name'] . ", " . $side['name'];
                                            } else if (isset($customization['pastry1'])) {
                                                // Pastry Duo customization
                                                $pastry1Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pastry1']);
                                                // FIXED: Added error checking before calling fetch_assoc()
                                                if ($pastry1Result && $pastry1Result->num_rows > 0) {
                                                    $pastry1 = $pastry1Result->fetch_assoc();
                                                } else {
                                                    $pastry1 = array('name' => 'Unknown Pastry');
                                                }
                                                $pastry2Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pastry2']);
                                                // FIXED: Added error checking before calling fetch_assoc()
                                                if ($pastry2Result && $pastry2Result->num_rows > 0) {
                                                    $pastry2 = $pastry2Result->fetch_assoc();
                                                } else {
                                                    $pastry2 = array('name' => 'Unknown Pastry');
                                                }
                                                echo "Custom: " . $pastry1['name'] . ", " . $pastry2['name'];
                                            }
                                            ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($imageUrl): ?>
                                        <img src="<?= $imageUrl ?>" alt="<?= $name ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </td>
                                <td><?= $qty ?></td>
                                <td>
                                    ₹<?= number_format($price, 2) ?>
                                    <?php if (strpos($cartKey, '_special') !== false && isset($originalPrice)): ?>
                                        <br><span class="text-muted text-decoration-line-through">
                                            <?php 
                                            // Show specific strikethrough prices for certain items
                                            if (isset($_SESSION['special_items'][$cartKey]) && isset($_SESSION['special_items'][$cartKey]['name'])) {
                                                $itemName = $_SESSION['special_items'][$cartKey]['name'];
                                                if ($itemName == 'Family Feast') {
                                                    echo '₹2200';
                                                } else if ($itemName == 'Pizza & Beverage Combo') {
                                                    echo '₹799';
                                                } else if ($itemName == '3 Mini Dips') {
                                                    echo '₹200';
                                                } else {
                                                    echo '₹' . number_format($originalPrice, 2);
                                                }
                                            } else if (isset($_SESSION['special_items'][$pizza_id]) && isset($_SESSION['special_items'][$pizza_id]['name'])) {
                                                $itemName = $_SESSION['special_items'][$pizza_id]['name'];
                                                if ($itemName == 'Family Feast') {
                                                    echo '₹2200';
                                                } else if ($itemName == 'Pizza & Beverage Combo') {
                                                    echo '₹799';
                                                } else if ($itemName == '3 Mini Dips') {
                                                    echo '₹200';
                                                } else {
                                                    echo '₹' . number_format($originalPrice, 2);
                                                }
                                            } else {
                                                echo '₹' . number_format($originalPrice, 2);
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>₹<?= number_format($total, 2) ?></td>
                                <td>
                                    <a href="order.php?remove=<?= $cartKey ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this item from your cart?')">Remove</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <?php
                            // Calculate delivery charges (if area is selected)
                            $deliveryCharge = 0;
                            if (isset($_SESSION['delivery_area']) && isset($deliveryCharges[$_SESSION['delivery_area']])) {
                                $deliveryCharge = $deliveryCharges[$_SESSION['delivery_area']];
                                $grandTotal += $deliveryCharge;
                            }
                            ?>
                            <tr>
                                <th colspan="4" class="text-end">Subtotal</th>
                                <th>₹<?= number_format($grandTotal - $deliveryCharge, 2) ?></th>
                                <th></th>
                            </tr>
                            <?php if ($deliveryCharge > 0): ?>
                            <tr>
                                <th colspan="4" class="text-end">Delivery Charges</th>
                                <th>₹<?= number_format($deliveryCharge, 2) ?></th>
                                <th></th>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th colspan="4" class="text-end">Grand Total</th>
                                <th>₹<?= number_format($grandTotal, 2) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <!-- Generated Bill -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-success text-white text-center">
                            <h4 class="mb-0">ORDER BILL</h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Customer:</strong> <?= htmlspecialchars($_SESSION['customer_name']) ?><br>
                                    <strong>Address:</strong> <?= htmlspecialchars($_SESSION['customer_address']) ?>
                                </div>
                                <div class="col-6 text-end">
                                    <strong>Order Bill ID:</strong> <?= isset($_SESSION['order_bill_id']) ? htmlspecialchars($_SESSION['order_bill_id']) : 'N/A' ?><br>
                                    <strong>Payment Method:</strong> 
                                    <?php 
                                    switch ($_SESSION['payment_method']) {
                                        case 'cod': echo 'Cash on Delivery'; break;
                                        case 'upi': echo 'UPI Payment'; break;
                                        case 'card': echo 'Credit/Debit Card'; break;
                                        default: echo 'Not specified';
                                    }
                                    ?><br>
                                    <strong>Order Time:</strong> <?= date('d M Y, H:i') ?>
                                </div>
                            </div>
                            
                            <h5 class="mb-3">Order Details</h5>
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Image</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $grandTotal = 0;
                                    foreach ($cart as $cartKey => $qty):
                                        // Check if this is a special item
                                        if (strpos($cartKey, '_special') !== false || strpos($cartKey, '_bestseller') !== false) {
                                            // Check if this is a customized item
                                            if (isset($_SESSION['special_items'][$cartKey])) {
                                                // Customized special item
                                                $item = $_SESSION['special_items'][$cartKey];
                                                $price = $item['price'];
                                                $name = $item['name'];
                                                $originalPrice = $item['original_price'];
                                                $customization = isset($item['customization']) ? $item['customization'] : null;
                                                
                                                // Get image (use the combo offer image)
                                                $parts = explode('_', $cartKey);
                                                $pizza_id = $parts[0]; // Extract ID from key
                                                $imageResult = $conn->query("SELECT image FROM menu WHERE id = $pizza_id");
                                                // FIXED: Added error checking before calling fetch_assoc()
                                                if ($imageResult && $imageResult->num_rows > 0) {
                                                    $imageRow = $imageResult->fetch_assoc();
                                                    $imageUrl = isset($imageRow['image']) ? $imageRow['image'] . '?v=' . time() : '';
                                                } else {
                                                    $imageUrl = '';
                                                }
                                                
                                                $total = $price * $qty;
                                                $grandTotal += $total;
                                            } else {
                                                // Non-customized special item
                                                $pizza_id = str_replace('_special', '', $cartKey);
                                                $pizza_id = str_replace('_bestseller', '', $pizza_id);
                                                
                                                // Get special item info
                                                if (isset($_SESSION['special_items'][$pizza_id])) {
                                                    $item = $_SESSION['special_items'][$pizza_id];
                                                    $price = $item['price'];
                                                    $name = $item['name'];
                                                    $originalPrice = $item['original_price'];
                                                    $customization = null;
                                                    
                                                    // Get image
                                                    $imageResult = $conn->query("SELECT image FROM menu WHERE id = $pizza_id");
                                                    // FIXED: Added error checking before calling fetch_assoc()
                                                    if ($imageResult && $imageResult->num_rows > 0) {
                                                        $imageRow = $imageResult->fetch_assoc();
                                                        $imageUrl = isset($imageRow['image']) ? $imageRow['image'] . '?v=' . time() : '';
                                                    } else {
                                                        $imageUrl = '';
                                                    }
                                                    
                                                    $total = $price * $qty;
                                                    $grandTotal += $total;
                                                } else {
                                                    // Fallback to regular item
                                                    continue;
                                                }
                                            }
                                        } else {
                                            // Regular item
                                            $pizza_id = $cartKey;
                                            $result = $conn->query("SELECT * FROM menu WHERE id = $pizza_id");
                                            // FIXED: Added error checking before calling fetch_assoc()
                                            if ($result && $result->num_rows > 0) {
                                            $pizza = $result->fetch_assoc();
                                            $price = $pizza['price'];
                                            $name = $pizza['name'];
                                            } else {
                                            // Fallback values if query fails
                                            $price = 0;
                                            $name = "Unknown Item";
                                            }
                                            $customization = null;
                                            
                                            // Get image
                                            if (isset($pizza) && isset($pizza['image'])) {
                                                $imageUrl = $pizza['image'] . '?v=' . time();
                                            } else {
                                                $imageUrl = '';
                                            }
                                            
                                            $total = $price * $qty;
                                            $grandTotal += $total;
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <?= $name ?>
                                            <?php if (strpos($cartKey, '_special') !== false || strpos($cartKey, '_bestseller') !== false): ?>
                                                <span class="badge bg-success">Special Offer</span>
                                            <?php endif; ?>
                                            <?php if ($customization): ?>
                                                <br><small class="text-muted">
                                                    <?php 
                                                    if (isset($customization['pizza1'])) {
                                                        // Family Feast customization
                                                        $pizza1Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pizza1']);
                                                        // FIXED: Added error checking before calling fetch_assoc()
                                                        if ($pizza1Result && $pizza1Result->num_rows > 0) {
                                                            $pizza1 = $pizza1Result->fetch_assoc();
                                                        } else {
                                                            $pizza1 = array('name' => 'Unknown Pizza');
                                                        }
                                                        $pizza2Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pizza2']);
                                                        // FIXED: Added error checking before calling fetch_assoc()
                                                        if ($pizza2Result && $pizza2Result->num_rows > 0) {
                                                            $pizza2 = $pizza2Result->fetch_assoc();
                                                        } else {
                                                            $pizza2 = array('name' => 'Unknown Pizza');
                                                        }
                                                        $beverage1Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['beverage1']);
                                                        // FIXED: Added error checking before calling fetch_assoc()
                                                        if ($beverage1Result && $beverage1Result->num_rows > 0) {
                                                            $beverage1 = $beverage1Result->fetch_assoc();
                                                        } else {
                                                            $beverage1 = array('name' => 'Unknown Beverage');
                                                        }
                                                        $beverage2Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['beverage2']);
                                                        // FIXED: Added error checking before calling fetch_assoc()
                                                        if ($beverage2Result && $beverage2Result->num_rows > 0) {
                                                            $beverage2 = $beverage2Result->fetch_assoc();
                                                        } else {
                                                            $beverage2 = array('name' => 'Unknown Beverage');
                                                        }
                                                        $dessertResult = $conn->query("SELECT name FROM menu WHERE id = " . $customization['dessert']);
                                                        // FIXED: Added error checking before calling fetch_assoc()
                                                        if ($dessertResult && $dessertResult->num_rows > 0) {
                                                            $dessert = $dessertResult->fetch_assoc();
                                                        } else {
                                                            $dessert = array('name' => 'Unknown Dessert');
                                                        }
                                                        $sideResult = $conn->query("SELECT name FROM menu WHERE id = " . $customization['side']);
                                                        // FIXED: Added error checking before calling fetch_assoc()
                                                        if ($sideResult && $sideResult->num_rows > 0) {
                                                            $side = $sideResult->fetch_assoc();
                                                        } else {
                                                            $side = array('name' => 'Unknown Side');
                                                        }
                                                        echo "Custom: " . $pizza1['name'] . ", " . $pizza2['name'] . ", " . $beverage1['name'] . ", " . $beverage2['name'] . ", " . $dessert['name'] . ", " . $side['name'];
                                                    } else if (isset($customization['pizza'])) {
                                                        // Pizza & Beverage Combo customization
                                                        $pizzaResult = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pizza']);
                                                        // FIXED: Added error checking before calling fetch_assoc()
                                                        if ($pizzaResult && $pizzaResult->num_rows > 0) {
                                                            $pizza = $pizzaResult->fetch_assoc();
                                                        } else {
                                                            $pizza = array('name' => 'Unknown Pizza');
                                                        }
                                                        $beverageResult = $conn->query("SELECT name FROM menu WHERE id = " . $customization['beverage']);
                                                        // FIXED: Added error checking before calling fetch_assoc()
                                                        if ($beverageResult && $beverageResult->num_rows > 0) {
                                                            $beverage = $beverageResult->fetch_assoc();
                                                        } else {
                                                            $beverage = array('name' => 'Unknown Beverage');
                                                        }
                                                        echo "Custom: " . $pizza['name'] . ", " . $beverage['name'];
                                                    } else if (isset($customization['pastry1'])) {
                                                        // Pastry Duo customization
                                                        $pastry1Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pastry1']);
                                                        // FIXED: Added error checking before calling fetch_assoc()
                                                        if ($pastry1Result && $pastry1Result->num_rows > 0) {
                                                            $pastry1 = $pastry1Result->fetch_assoc();
                                                        } else {
                                                            $pastry1 = array('name' => 'Unknown Pastry');
                                                        }
                                                        $pastry2Result = $conn->query("SELECT name FROM menu WHERE id = " . $customization['pastry2']);
                                                        // FIXED: Added error checking before calling fetch_assoc()
                                                        if ($pastry2Result && $pastry2Result->num_rows > 0) {
                                                            $pastry2 = $pastry2Result->fetch_assoc();
                                                        } else {
                                                            $pastry2 = array('name' => 'Unknown Pastry');
                                                        }
                                                        echo "Custom: " . $pastry1['name'] . ", " . $pastry2['name'];
                                                    }
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($imageUrl): ?>
                                                <img src="<?= $imageUrl ?>" alt="<?= $name ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $qty ?></td>
                                        <td>
                                            ₹<?= number_format($price, 2) ?>
                                            <?php if ((strpos($cartKey, '_special') !== false || strpos($cartKey, '_bestseller') !== false) && isset($originalPrice)): ?>
                                                <br><span class="text-muted text-decoration-line-through">
                                                    <?php 
                                                    // Show specific strikethrough prices for certain items
                                                    if (isset($_SESSION['special_items'][$cartKey]) && isset($_SESSION['special_items'][$cartKey]['name'])) {
                                                        $itemName = $_SESSION['special_items'][$cartKey]['name'];
                                                        if ($itemName == 'Family Feast') {
                                                            echo '₹2200';
                                                        } else if ($itemName == 'Pizza & Beverage Combo') {
                                                            echo '₹799';
                                                        } else if ($itemName == '3 Mini Dips') {
                                                            echo '₹200';
                                                        } else {
                                                            echo '₹' . number_format($originalPrice, 2);
                                                        }
                                                    } else if (isset($_SESSION['special_items'][$pizza_id]) && isset($_SESSION['special_items'][$pizza_id]['name'])) {
                                                        $itemName = $_SESSION['special_items'][$pizza_id]['name'];
                                                        if ($itemName == 'Family Feast') {
                                                            echo '₹2200';
                                                        } else if ($itemName == 'Pizza & Beverage Combo') {
                                                            echo '₹799';
                                                        } else if ($itemName == '3 Mini Dips') {
                                                            echo '₹200';
                                                        } else {
                                                            echo '₹' . number_format($originalPrice, 2);
                                                        }
                                                    } else {
                                                        echo '₹' . number_format($originalPrice, 2);
                                                    }
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>₹<?= number_format($total, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <?php
                                    // Calculate delivery charges
                                    $deliveryCharge = 0;
                                    if (isset($_SESSION['delivery_area']) && isset($deliveryCharges[$_SESSION['delivery_area']])) {
                                        $deliveryCharge = $deliveryCharges[$_SESSION['delivery_area']];
                                        $grandTotal += $deliveryCharge;
                                    }
                                    ?>
                                    <tr>
                                        <th colspan="4" class="text-end">Subtotal</th>
                                        <th>₹<?= number_format($grandTotal - $deliveryCharge, 2) ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">Delivery Charges</th>
                                        <th>₹<?= number_format($deliveryCharge, 2) ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">Grand Total</th>
                                        <th>₹<?= number_format($grandTotal, 2) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <div class="alert alert-info">
                                <strong>Estimated Delivery Time:</strong> <?= $_SESSION['delivery_time'] ?>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="confirm_order" value="1">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">Confirm Order & Pay</button>
                                    <a href="order.php" class="btn btn-outline-secondary">Back to Edit</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript with jQuery -->
<script src="js/validate.js"></script>

<script>
$(document).ready(function() {
    // Form validation
    $('#order-form').on('submit', function(e) {
        const name = $('#customer_name').val().trim();
        const address = $('#customer_address').val().trim();
        const payment = $('#payment_method').val();
        
        if (name.length < 2) {
            e.preventDefault();
            alert("Please enter a valid name (at least 2 characters)");
            $('#customer_name').focus();
            return false;
        }
        
        if (address.length < 10) {
            e.preventDefault();
            alert("Please enter a valid address (at least 10 characters)");
            $('#customer_address').focus();
            return false;
        }
        
        if (payment === "") {
            e.preventDefault();
            alert("Please select a payment method");
            $('#payment_method').focus();
            return false;
        }
    });
    
    // Timer countdown for order cancellation
    <?php if (isset($_SESSION['order_time']) && $showConfirmation): 
        $orderTime = $_SESSION['order_time'];
        $currentTime = time();
        $timeDiff = $currentTime - $orderTime;
        $timeLeft = 600 - $timeDiff; // 10 minutes in seconds
        if ($timeLeft > 0):
    ?>
    let timeLeft = <?= $timeLeft ?>;
    const timerElement = document.getElementById('timer');
    
    const countdown = setInterval(function() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        if (timerElement) {
            timerElement.textContent = `You have ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')} left to cancel this order`;
        }
        
        timeLeft--;
        
        if (timeLeft < 0) {
            clearInterval(countdown);
            if (timerElement) {
                timerElement.textContent = "Cancellation window has expired";
            }
            // Reload page to show updated message
            setTimeout(function() {
                location.reload();
            }, 2000);
        }
    }, 1000);
    <?php endif; endif; ?>
});
</script>
</body>
</html>