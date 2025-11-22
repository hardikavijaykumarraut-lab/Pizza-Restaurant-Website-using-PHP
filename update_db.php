<?php
include 'db.php';

// Update existing menu items to have categories if they don't already
$conn->query("UPDATE menu SET category='pizza', type='veg' WHERE name IN ('Margherita', 'Paneer Tikka', 'Veggie Delight')");
$conn->query("UPDATE menu SET category='pizza', type='non-veg' WHERE name IN ('BBQ Chicken', 'Pepperoni')");

echo "Database updated successfully!";
?>