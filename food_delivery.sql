-- Create the database
CREATE DATABASE IF NOT EXISTS food_delivery;
USE food_delivery;

-- Admin Table
CREATE TABLE Admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(100), -- For password reset
    reset_token_expires DATETIME, -- Token expiration time
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Restaurant Table
CREATE TABLE Restaurant (
    restaurant_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    image_url VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    avg_rating DECIMAL(3,2) DEFAULT 0.00,
    reset_token VARCHAR(100), -- For password reset
    reset_token_expires DATETIME, -- Token expiration time
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Food Item Table
CREATE TABLE FoodItem (
    food_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    restaurant_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES Restaurant(restaurant_id) ON DELETE CASCADE
);

-- Customer Table
CREATE TABLE Customer (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    address VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(100), -- For password reset
    reset_token_expires DATETIME, -- Token expiration time
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Delivery Person Table
CREATE TABLE DeliveryPerson (
    delivery_person_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    address VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    avg_rating DECIMAL(3,2) DEFAULT 0.00,
    reset_token VARCHAR(100), -- For password reset
    reset_token_expires DATETIME, -- Token expiration time
    status ENUM('available', 'busy') DEFAULT 'available', -- Track availability
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Order Table
CREATE TABLE Orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    restaurant_id INT,
    delivery_person_id INT, -- Track assigned delivery person
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    customer_location VARCHAR(255) NOT NULL, -- Customer's delivery address
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE SET NULL,
    FOREIGN KEY (restaurant_id) REFERENCES Restaurant(restaurant_id) ON DELETE SET NULL,
    FOREIGN KEY (delivery_person_id) REFERENCES DeliveryPerson(delivery_person_id) ON DELETE SET NULL
);

-- Order Item Table
CREATE TABLE OrderItem (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    food_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES FoodItem(food_id) ON DELETE SET NULL
);

-- Payment Table (Optional: For tracking payments)
CREATE TABLE Payment (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    payment_method ENUM('Credit Card', 'Mobile Money', 'Cash on Delivery') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('Pending', 'Completed', 'Failed') DEFAULT 'Pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE
);

-- Notification Table (Optional: For tracking notifications)
CREATE TABLE Notification (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT, -- Can be customer_id, restaurant_id, or delivery_person_id
    user_role ENUM('customer', 'restaurant', 'delivery_person') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Add a table for ratings and feedback
CREATE TABLE Ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    delivery_person_id INT NOT NULL,
    food_rating TINYINT NOT NULL CHECK (food_rating BETWEEN 1 AND 5),
    delivery_rating TINYINT NOT NULL CHECK (delivery_rating BETWEEN 1 AND 5),
    feedback_text TEXT,
    rating_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id),
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id),
    FOREIGN KEY (restaurant_id) REFERENCES Restaurant(restaurant_id),
    FOREIGN KEY (delivery_person_id) REFERENCES DeliveryPerson(delivery_person_id)
);

