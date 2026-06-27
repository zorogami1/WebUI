-- 1. Create and initialize the database using your environment's exact naming convention
CREATE DATABASE IF NOT EXISTS `createprojectdb`;
USE `createprojectdb`;

-- 2. Drop existing structures to prevent foreign key compilation conflicts during updates
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;

-- 3. Create the users table structured to handle registration form inputs
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `contact_number` VARCHAR(20) NOT NULL,
    `delivery_address` TEXT NOT NULL,
    `company_name` VARCHAR(100) DEFAULT NULL,
    `role` ENUM('customer', 'staff') NOT NULL DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create the products master table tracking inventory attributes
CREATE TABLE `products` (
    `product_id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_name` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `description` TEXT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Seed the products catalog with your exact 6 handcrafted furniture collection items
INSERT INTO `products` (`product_id`, `product_name`, `price`, `description`, `image_path`) VALUES
(1, 'Oak Dining Chair', 450.00, 'Solid oak, timeless elegance', '../images/1.png'),
(2, 'Large Dining Table', 2500.00, 'Seats 6, modern design', '../images/2.png'),
(3, '3-Seater Fabric Sofa', 3800.00, 'Premium comfort', '../images/3.png'),
(4, 'Wooden Wardrobe', 1800.00, 'Spacious, elegant storage', '../images/4.png'),
(5, 'Industrial Bookshelf', 1200.00, 'Modern steel frame', '../images/5.png'),
(6, 'Queen Size Bed Frame', 2200.00, 'Sturdy, timeless design', '../images/6.png');

-- 6. Create the relational orders table with safe integrity constraint chains
CREATE TABLE `orders` (
    `order_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `order_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('Pending', 'Processing', 'Delivered', 'Completed') NOT NULL DEFAULT 'Pending',
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_orders_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;