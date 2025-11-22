<?php
include 'db.php';

echo "<h2>Menu Items in Database:</h2>";
$result = $conn->query("SELECT * FROM menu ORDER BY category, display_order");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Display Order</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['category'] . "</td>";
        echo "<td>₹" . number_format($row['price'], 2) . "</td>";
        echo "<td>" . $row['display_order'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No items found in the menu table.";
}

echo "<h2>Categories:</h2>";
$categories = $conn->query("SELECT DISTINCT category FROM menu");
if ($categories && $categories->num_rows > 0) {
    echo "<ul>";
    while ($row = $categories->fetch_assoc()) {
        echo "<li>" . $row['category'] . "</li>";
    }
    echo "</ul>";
}
?>