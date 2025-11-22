<?php
include 'db.php';

// Fetch all orders to see what's in the database
$result = $conn->query("SELECT * FROM orders ORDER BY order_bill_id, id");

echo "<h2>Orders in Database</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Order Bill ID</th><th>Customer Name</th><th>Pizza ID</th><th>Quantity</th><th>Status</th></tr>";

while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['order_bill_id'] . "</td>";
    echo "<td>" . $row['customer_name'] . "</td>";
    echo "<td>" . $row['pizza_id'] . "</td>";
    echo "<td>" . $row['quantity'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test the specific query used in admin panel
echo "<h2>Testing Admin Query</h2>";
$testBillId = 'ORD-TEST123'; // Replace with an actual order bill ID from your database
$itemsResult = $conn->query("SELECT o.quantity, m.name AS item_name, o.pizza_id
                            FROM orders o 
                            JOIN menu m ON o.pizza_id = m.id 
                            WHERE o.order_bill_id = '$testBillId'");

echo "<p>Query: SELECT o.quantity, m.name AS item_name, o.pizza_id FROM orders o JOIN menu m ON o.pizza_id = m.id WHERE o.order_bill_id = '$testBillId'</p>";

if ($itemsResult && $itemsResult->num_rows > 0) {
    echo "<p>Found " . $itemsResult->num_rows . " items</p>";
    echo "<ul>";
    while($item = $itemsResult->fetch_assoc()) {
        echo "<li>" . $item['quantity'] . " x " . $item['item_name'] . " (ID: " . $item['pizza_id'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No items found for order bill ID: $testBillId</p>";
}
?>