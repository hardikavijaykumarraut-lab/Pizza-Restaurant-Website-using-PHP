<?php
include 'db.php';

// Test the admin panel query with our test order
$testBillId = 'ORD-TEST2AC4';

echo "<h2>Testing Admin Query for Order Bill ID: $testBillId</h2>";

// Get the order summary (like in admin panel)
$result = $conn->query("SELECT o.order_bill_id, o.customer_name, o.order_time, 
                        CASE 
                            WHEN SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) = COUNT(*) THEN 'cancelled'
                            WHEN SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) = COUNT(*) THEN 'delivered'
                            ELSE 'preparing'
                        END as status
                        FROM orders o 
                        WHERE o.order_bill_id = '$testBillId'
                        GROUP BY o.order_bill_id, o.customer_name, o.order_time");

if ($result && $result->num_rows > 0) {
    $order = $result->fetch_assoc();
    echo "<p>Order Summary:</p>";
    echo "<ul>";
    echo "<li>Order Bill ID: " . $order['order_bill_id'] . "</li>";
    echo "<li>Customer Name: " . $order['customer_name'] . "</li>";
    echo "<li>Order Time: " . $order['order_time'] . "</li>";
    echo "<li>Status: " . $order['status'] . "</li>";
    echo "</ul>";
    
    // Get all items for this order (like in admin panel)
    $itemsResult = $conn->query("SELECT o.quantity, m.name AS item_name, o.pizza_id
                                FROM orders o 
                                JOIN menu m ON o.pizza_id = m.id 
                                WHERE o.order_bill_id = '" . $conn->real_escape_string($order['order_bill_id']) . "'");
    
    $items = array();
    $totalQuantity = 0;
    echo "<p>Items in order:</p>";
    echo "<ul>";
    while($item = $itemsResult->fetch_assoc()) {
        echo "<li>" . $item['quantity'] . " x " . $item['item_name'] . " (ID: " . $item['pizza_id'] . ")</li>";
        $items[] = $item['quantity'] . ' x ' . $item['item_name'];
        $totalQuantity += $item['quantity'];
    }
    echo "</ul>";
    
    echo "<p>Total Items: " . $totalQuantity . "</p>";
    echo "<p>Items List: " . implode(', ', $items) . "</p>";
} else {
    echo "<p>No order found with bill ID: $testBillId</p>";
}
?>