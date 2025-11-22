<?php
// Copy this file to `db.php` and fill in your local credentials.
$host = "localhost";
$user = "root";
$password = ""; // set your DB password here
$dbname = "pizza_restaurant";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
