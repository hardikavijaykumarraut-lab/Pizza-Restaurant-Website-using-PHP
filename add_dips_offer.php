<?php
include 'db.php';

// Check if the 3 Mini Dips offer already exists
$check = $conn->query("SELECT * FROM menu WHERE name = '3 Mini Dips'");
if ($check->num_rows > 0) {
    echo "3 Mini Dips offer already exists in the database.\n";
} else {
    // Insert the 3 Mini Dips offer
    $sql = "INSERT INTO menu (name, description, price, image, display_order, category) VALUES 
            ('3 Mini Dips', 'A delicious combination of our three signature dips - Cheese, Garlic, and Spicy Mayo', 150, 'assets/3_mini_dips.jpg', 4, 'dip')";
    
    if ($conn->query($sql) === TRUE) {
        echo "3 Mini Dips offer added successfully!\n";
        echo "Regular price: ₹200\n";
        echo "Special offer price: ₹150\n";
    } else {
        echo "Error adding 3 Mini Dips offer: " . $conn->error . "\n";
    }
}

// Also update the individual dips to show their regular price
$conn->query("UPDATE menu SET price = 79 WHERE name = 'Cheese Dip'");
$conn->query("UPDATE menu SET price = 69 WHERE name = 'Garlic Dip'");
$conn->query("UPDATE menu SET price = 69 WHERE name = 'Spicy Mayo'");

echo "Individual dip prices updated to regular prices.\n";
?>