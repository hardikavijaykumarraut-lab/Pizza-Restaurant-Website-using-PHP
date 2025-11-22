<?php
include 'db.php';

// Drop the menu table and recreate it with the correct structure
$conn->query("DROP TABLE IF EXISTS menu");

// Create the menu table with the correct structure
$conn->query("CREATE TABLE menu ( 
    id INT AUTO_INCREMENT PRIMARY KEY, 
    name VARCHAR(100) NOT NULL, 
    description TEXT, 
    price DECIMAL(5,2) NOT NULL, 
    image VARCHAR(255), 
    display_order INT DEFAULT 0,
    category VARCHAR(50) DEFAULT 'pizza'
)");

// Insert sample pizzas with custom display order
$conn->query("INSERT INTO menu (name, description, price, image, display_order, category) VALUES
('Margherita', 'Classic cheese and tomato', 599, 'assets/pizza1.jpg', 1, 'pizza'),
('Paneer Tikka', 'Spicy paneer with Indian spices', 699, 'assets/pizza5.jpg', 2, 'pizza'),
('Veggie Delight', 'Onions, peppers, mushrooms', 649, 'assets/pizza3.jpg', 3, 'pizza'),
('BBQ Chicken', 'Grilled chicken with BBQ sauce', 749, 'assets/pizza4.jpg', 4, 'pizza'),
('Pepperoni', 'Pepperoni and mozzarella', 699, 'assets/pizza2.jpg', 5, 'pizza')");

// Insert beverages
$conn->query("INSERT INTO menu (name, description, price, image, display_order, category) VALUES
('Cold Coffee', 'Refreshing cold coffee with ice cream', 149, 'assets/bev_coffee.jpeg', 1, 'beverage'),
('Mocktail', 'Fresh fruit mocktail', 129, 'assets/bev_mocktail.jpeg', 2, 'beverage'),
('Hot Tea', 'Freshly brewed tea', 49, 'assets/bev_tea.jpeg', 3, 'beverage')");

// Insert sides
$conn->query("INSERT INTO menu (name, description, price, image, display_order, category) VALUES
('Garlic Bread', 'Crispy garlic bread with herbs', 129, 'assets/garlic_bread.jpg', 1, 'side'),
('Potato Wedges', 'Crispy potato wedges with seasoning', 149, 'assets/potato_wedges.jpg', 2, 'side'),
('Chicken Wings', 'Spicy chicken wings', 199, 'assets/chicken_wings.jpg', 3, 'side')");

// Insert dips
$conn->query("INSERT INTO menu (name, description, price, image, display_order, category) VALUES
('Cheese Dip', 'Creamy cheese dip', 79, 'assets/cheese_dip.jpg', 1, 'dip'),
('Garlic Dip', 'Garlic flavored dip', 69, 'assets/garlic_dip.jpg', 2, 'dip'),
('Spicy Mayo', 'Spicy mayo dip', 69, 'assets/spicy_mayo.jpg', 3, 'dip')");

echo "Menu table reset successfully!";
?>