<?php
include 'db.php';

// Add order_bill_id column to orders table if it doesn't exist
$sql = "ALTER TABLE orders ADD COLUMN order_bill_id VARCHAR(20) AFTER id";
if ($conn->query($sql) === TRUE) {
    echo "Column order_bill_id added successfully\n";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "Column order_bill_id already exists\n";
    } else {
        echo "Error adding order_bill_id column: " . $conn->error . "\n";
    }
}

// Add status column to orders table if it doesn't exist
$sql = "ALTER TABLE orders ADD COLUMN status VARCHAR(20) DEFAULT 'preparing'";
if ($conn->query($sql) === TRUE) {
    echo "Column status added successfully\n";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "Column status already exists\n";
    } else {
        echo "Error adding status column: " . $conn->error . "\n";
    }
}

echo "Missing columns check completed!";
?>