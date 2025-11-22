<?php
include 'db.php';

// Read the SQL file
$sql = file_get_contents('pizza_restaurant.sql');

// Split the SQL file into individual statements
$statements = explode(';', $sql);

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        if (!$conn->query($statement)) {
            echo "Error executing statement: " . $conn->error . "\n";
        }
    }
}

echo "Database initialized successfully!";
?>