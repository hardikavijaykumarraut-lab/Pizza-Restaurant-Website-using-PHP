<?php
include 'db.php';

// Delete duplicate entries, keeping only the first occurrence of each item
$conn->query("DELETE m1 FROM menu m1 INNER JOIN menu m2 WHERE m1.id > m2.id AND m1.name = m2.name");

echo "Database cleaned successfully!";
?>