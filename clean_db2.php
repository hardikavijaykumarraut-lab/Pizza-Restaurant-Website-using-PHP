<?php
include 'db.php';

// Create a more specific query to delete duplicates
// Keep the item with the smallest ID for each name
$conn->query("DELETE FROM menu WHERE id NOT IN (SELECT * FROM (SELECT MIN(id) FROM menu GROUP BY name) AS temp)");

echo "Database cleaned successfully!";
?>