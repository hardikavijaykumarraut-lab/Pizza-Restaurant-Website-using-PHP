<?php
session_start();
include 'db.php';

// Simulate adding multiple items to cart
$_SESSION['cart'] = array(
    '1' => 2,  // 2 of item 1
    '2' => 1,  // 1 of item 2
    '3_special' => 3  // 3 of special item 3
);

$_SESSION['special_items'] = array(
    '3' => array(
        'name' => 'Special Pizza',
        'price' => 599,
        'original_price' => 699
    )
);

// Display cart contents
echo "<h2>Cart Contents</h2>";
echo "<pre>";
print_r($_SESSION['cart']);
echo "</pre>";

echo "<h2>Special Items</h2>";
echo "<pre>";
print_r($_SESSION['special_items']);
echo "</pre>";

// Simulate order confirmation process
$name = 'Test Customer';
$address = 'Test Address';
$orderBillId = 'ORD-TEST' . strtoupper(substr(md5(time()), 0, 4));

echo "<h2>Processing Order</h2>";
echo "<p>Order Bill ID: $orderBillId</p>";

// Clear any existing test orders
$conn->query("DELETE FROM orders WHERE order_bill_id = '$orderBillId'");

$cart = $_SESSION['cart'];
foreach ($cart as $cartKey => $qty) {
    echo "<p>Processing cart item: $cartKey with quantity $qty</p>";
    
    // Check if this is a special item
    if (strpos($cartKey, '_special') !== false || strpos($cartKey, '_bestseller') !== false) {
        // Non-customized special item
        $pizza_id = str_replace('_special', '', $cartKey);
        $pizza_id = str_replace('_bestseller', '', $pizza_id);
        
        echo "<p>Special item ID: $pizza_id</p>";
        
        // Get special item info
        if (isset($_SESSION['special_items'][$pizza_id])) {
            $item = $_SESSION['special_items'][$pizza_id];
            echo "<p>Found special item: " . $item['name'] . "</p>";
        }
    } else {
        // Regular item
        $pizza_id = $cartKey;
        echo "<p>Regular item ID: $pizza_id</p>";
    }
    
    // Insert into database
    $insertResult = $conn->query("INSERT INTO orders (order_bill_id, customer_name, customer_address, pizza_id, quantity) VALUES ('$orderBillId', '$name', '$address', $pizza_id, $qty)");
    if ($insertResult) {
        echo "<p>Inserted item successfully</p>";
    } else {
        echo "<p>Error inserting item: " . $conn->error . "</p>";
    }
}

// Check what was inserted
echo "<h2>Orders Inserted</h2>";
$result = $conn->query("SELECT * FROM orders WHERE order_bill_id = '$orderBillId'");
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Order Bill ID</th><th>Customer Name</th><th>Pizza ID</th><th>Quantity</th></tr>";

while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['order_bill_id'] . "</td>";
    echo "<td>" . $row['customer_name'] . "</td>";
    echo "<td>" . $row['pizza_id'] . "</td>";
    echo "<td>" . $row['quantity'] . "</td>";
    echo "</tr>";
}

echo "</table>";
?>