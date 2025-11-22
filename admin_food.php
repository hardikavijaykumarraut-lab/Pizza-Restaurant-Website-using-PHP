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

$error = "";
$success = "";

// Handle Add New Food Item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = floatval($_POST['price']);
    $category = $conn->real_escape_string($_POST['category']);
    $display_order = intval($_POST['display_order']);
    $image = $conn->real_escape_string($_POST['image']);
    
    if (empty($name) || empty($price) || empty($category)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO menu (name, description, price, image, display_order, category) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssdsis", $name, $description, $price, $image, $display_order, $category);
            if ($stmt->execute()) {
                $success = "Food item added successfully!";
            } else {
                $error = "Error adding food item: " . $conn->error;
            }
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// Handle Update Food Item Price
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_price'])) {
    $id = intval($_POST['item_id']);
    $price = floatval($_POST['new_price']);
    
    $stmt = $conn->prepare("UPDATE menu SET price = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("di", $price, $id);
        if ($stmt->execute()) {
            $success = "Price updated successfully!";
        } else {
            $error = "Error updating price: " . $conn->error;
        }
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Handle Delete Food Item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_item'])) {
    $id = intval($_POST['item_id']);
    
    $stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Food item deleted successfully!";
        } else {
            $error = "Error deleting food item: " . $conn->error;
        }
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Fetch all food items
$items = $conn->query("SELECT * FROM menu ORDER BY category, display_order");
$categories = $conn->query("SELECT DISTINCT category FROM menu");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Food Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Adding jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                <li class="nav-item"><a class="nav-link" href="admin.php">Orders</a></li>
                <li class="nav-item"><a class="nav-link active" href="admin_food.php">Food Management</a></li>
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

<div class="container mt-5">
    <h2 class="text-center text-danger mb-4">🍔 Food Item Management</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <!-- Add New Food Item Form -->
    <div class="card mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Add New Food Item</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="add_item" value="1">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Item Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category" class="form-label">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="pizza">Pizza</option>
                                <option value="beverage">Beverage</option>
                                <option value="side">Side</option>
                                <option value="dip">Dip</option>
                                <option value="dessert">Dessert</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (₹) *</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="display_order" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="display_order" name="display_order" value="0">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="image" class="form-label">Image Path</label>
                    <input type="text" class="form-control" id="image" name="image" placeholder="assets/item.jpg">
                </div>
                
                <button type="submit" class="btn btn-primary">Add Food Item</button>
            </form>
        </div>
    </div>
    
    <!-- Food Items List -->
    <h3 class="mb-4">Current Food Items</h3>
    
    <?php if ($items && $items->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Price (₹)</th>
                        <th>Display Order</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?= $item['id'] ?></td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td>
                            <span class="badge bg-secondary"><?= htmlspecialchars($item['category']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($item['description']) ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="update_price" value="1">
                                <div class="input-group">
                                    <input type="number" class="form-control form-control-sm" name="new_price" value="<?= $item['price'] ?>" step="0.01" min="0" style="width: 100px;">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Update</button>
                                </div>
                            </form>
                        </td>
                        <td><?= $item['display_order'] ?></td>
                        <td>
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= $item['image'] ?>?v=<?= time() ?>" alt="<?= $item['name'] ?>" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <span class="text-muted">No image</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="delete_item" value="1">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">No food items found.</div>
    <?php endif; ?>
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