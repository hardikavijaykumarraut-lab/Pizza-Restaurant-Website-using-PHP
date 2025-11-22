<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Check if user is admin
if ($_SESSION['user'] !== 'admin@admin.com') {
    header("Location: index.php");
    exit;
}

// Handle order status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_bill_id = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $status, $order_id);
        $stmt->execute();
    }
}

// Fetch all orders with order_bill_id as the grouping key
// Group orders by order_bill_id and get the most relevant status
$result = $conn->query("SELECT o.order_bill_id, o.customer_name, o.order_time, 
                        CASE 
                            WHEN SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) = COUNT(*) THEN 'cancelled'
                            WHEN SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) = COUNT(*) THEN 'delivered'
                            ELSE 'preparing'
                        END as status
                        FROM orders o 
                        GROUP BY o.order_bill_id, o.customer_name, o.order_time
                        ORDER BY o.order_time DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Order Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Adding jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .status-preparing { background-color: #fff3cd; border-color: #ffeaa7; }
        .status-delivered { background-color: #d4edda; border-color: #c3e6cb; }
        .status-cancelled { background-color: #f8d7da; border-color: #f5c6cb; }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">🍕 Pizza Restro Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link active" href="#">Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_food.php">Food Management</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item"><span class="nav-link">Welcome, <?= htmlspecialchars($_SESSION['user']) ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Order Table -->
<div class="container mt-5">
    <h2 class="text-center text-danger mb-4">🧾 Order Summary</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Order Bill ID</th>
                        <th>Customer Name</th>
                        <th>Items</th>
                        <th>Order Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $result->fetch_assoc()): 
                        // Get all items for this order with their names and quantities
                        $itemsResult = $conn->query("SELECT o.quantity, m.name AS item_name, o.pizza_id
                                                    FROM orders o 
                                                    JOIN menu m ON o.pizza_id = m.id 
                                                    WHERE o.order_bill_id = '" . $conn->real_escape_string($order['order_bill_id']) . "'");
                        $items = array();
                        $totalQuantity = 0;
                        while($item = $itemsResult->fetch_assoc()) {
                            $items[] = $item['quantity'] . ' x ' . $item['item_name'];
                            $totalQuantity += $item['quantity'];
                        }
                        ?>
                    <tr class="status-<?= $order['status'] ?>">
                        <td><?= !empty($order['order_bill_id']) ? htmlspecialchars($order['order_bill_id']) : 'N/A' ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td>
                            <strong><?= $totalQuantity ?> items:</strong><br>
                            <?= implode('<br>', $items) ?>
                        </td>
                        <td><?= date('d M Y, H:i', strtotime($order['order_time'])) ?></td>
                        <td>
                            <span class="badge 
                                <?php 
                                switch ($order['status']) {
                                    case 'preparing': echo 'bg-warning'; break;
                                    case 'delivered': echo 'bg-success'; break;
                                    case 'cancelled': echo 'bg-danger'; break;
                                }
                                ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="order_id" value="<?= $order['order_bill_id'] ?>">
                                <select name="status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                    <option value="preparing" <?= $order['status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                    <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">No orders found.</div>
    <?php endif; ?>
    
    <!-- Order Statistics -->
    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card text-white bg-warning">
                <div class="card-header">Preparing</div>
                <div class="card-body">
                    <h5 class="card-title">
                        <?php
                        $prep_result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'preparing'");
                        $prep_row = $prep_result->fetch_assoc();
                        echo $prep_row['count'];
                        ?>
                    </h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-header">Delivered</div>
                <div class="card-body">
                    <h5 class="card-title">
                        <?php
                        $deliv_result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'");
                        $deliv_row = $deliv_result->fetch_assoc();
                        echo $deliv_row['count'];
                        ?>
                    </h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger">
                <div class="card-header">Cancelled</div>
                <div class="card-body">
                    <h5 class="card-title">
                        <?php
                        $cancel_result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'");
                        $cancel_row = $cancel_result->fetch_assoc();
                        echo $cancel_row['count'];
                        ?>
                    </h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white mt-5 py-4">
    <div class="container text-center">
        <p class="mb-0">&copy; 2025 Pizza Restro Admin Panel. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>