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
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID of the recipient user',
  `user_type` enum('customer','restaurant','delivery') NOT NULL COMMENT 'Type of user receiving the notification',
  `sender_id` int(11) DEFAULT NULL COMMENT 'ID of the user/system that triggered the notification',
  `sender_type` enum('system','customer','restaurant','delivery') DEFAULT 'system' COMMENT 'Type of sender',
  `message` varchar(255) NOT NULL COMMENT 'Notification content',
  `notification_type` enum('order','promotion','system','review','status_update') NOT NULL DEFAULT 'system' COMMENT 'Category of notification',
  `related_id` int(11) DEFAULT NULL COMMENT 'ID of related entity (order_id, etc)',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=unread, 1=read',
  `is_archived` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=active, 1=archived',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'When notification should auto-archive',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`,`user_type`),
  KEY `idx_read_status` (`user_id`,`is_read`),
  KEY `idx_created` (`created_at`),
  KEY `idx_related` (`related_id`,`notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='System notifications for all user types';
-- Optional: Add sample data for testing
INSERT INTO `notifications` 
(`user_id`, `user_type`, `sender_id`, `sender_type`, `message`, `notification_type`, `related_id`, `is_read`, `created_at`) 
VALUES
(1, 'customer', NULL, 'system', 'Your order #1234 has been confirmed', 'order', 1234, 0, NOW()),
(1, 'customer', 5, 'restaurant', 'Restaurant has started preparing your order', 'order', 1234, 0, NOW()),
(2, 'restaurant', 1, 'customer', 'New order received from Customer #1', 'order', 1234, 0, NOW()),
(3, 'delivery', NULL, 'system', 'New delivery assignment: Order #1234', 'order', 1234, 0, NOW()),
(1, 'customer', NULL, 'system', 'Special 20% discount on your next order!', 'promotion', NULL, 0, NOW());

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

