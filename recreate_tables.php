<?php
include 'db.php';

// Drop tables in correct order due to foreign key constraints
$conn->query("DROP TABLE IF EXISTS orders");
$conn->query("DROP TABLE IF EXISTS menu");

// Recreate the menu table with the correct structure
$sql = "CREATE TABLE menu ( 
    id INT AUTO_INCREMENT PRIMARY KEY, 
    name VARCHAR(100) NOT NULL, 
    description TEXT, 
    price DECIMAL(5,2) NOT NULL, 
    image VARCHAR(255), 
    display_order INT DEFAULT 0,
    category VARCHAR(50) DEFAULT 'pizza'
)";

if ($conn->query($sql) === TRUE) {
    echo "Menu table created successfully\n";
} else {
    echo "Error creating menu table: " . $conn->error . "\n";
}

// Recreate the orders table
$sql = "CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_address TEXT NOT NULL,
    pizza_id INT NOT NULL,
    quantity INT NOT NULL,
    order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pizza_id) REFERENCES menu(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Orders table created successfully\n";
} else {
    echo "Error creating orders table: " . $conn->error . "\n";
}

echo "Tables recreated successfully!";
?>