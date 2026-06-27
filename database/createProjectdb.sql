-- 1. Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `createprojectdb`;

-- 2. Select the database to work with
USE `createprojectdb`;

-- 3. Drop existing tables (in correct order to avoid foreign key issues)
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;

-- 4. Create the users table with all registration fields
CREATE TABLE `users` (
                         `id` INT AUTO_INCREMENT PRIMARY KEY,
                         `full_name` VARCHAR(100) NOT NULL,
                         `email` VARCHAR(100) NOT NULL UNIQUE,
                         `password` VARCHAR(255) NOT NULL,
                         `phone` VARCHAR(20) NOT NULL,
                         `role` ENUM('customer', 'staff') NOT NULL DEFAULT 'customer',

    -- Customer-specific fields
                         `delivery_address` TEXT NULL,
                         `date_of_birth` DATE NULL,

    -- Staff-specific fields
                         `id_number` VARCHAR(50) NULL COMMENT 'Government ID number (e.g., HKID)',
                         `bank_name` VARCHAR(100) NULL,
                         `bank_account` VARCHAR(50) NULL,
                         `emergency_contact` VARCHAR(100) NULL,
                         `emergency_phone` VARCHAR(20) NULL,
                         `certifications` TEXT NULL COMMENT 'Professional certifications and credentials',

    -- Timestamps
                         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                         `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                         INDEX `idx_email` (`email`),
                         INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Create the products master table
CREATE TABLE `products` (
                            `product_id` INT AUTO_INCREMENT PRIMARY KEY,
                            `product_name` VARCHAR(100) NOT NULL,
                            `price` DECIMAL(10,2) NOT NULL,
                            `description` TEXT NOT NULL,
                            `image_path` VARCHAR(255) NOT NULL,
                            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Seed the products catalog with your 6 handcrafted furniture collection items
INSERT INTO `products` (`product_id`, `product_name`, `price`, `description`, `image_path`) VALUES
                                                                                                (1, 'Oak Dining Chair', 450.00, 'Solid oak, timeless elegance', '../images/1.png'),
                                                                                                (2, 'Large Dining Table', 2500.00, 'Seats 6, modern design', '../images/2.png'),
                                                                                                (3, '3-Seater Fabric Sofa', 3800.00, 'Premium comfort', '../images/3.png'),
                                                                                                (4, 'Wooden Wardrobe', 1800.00, 'Spacious, elegant storage', '../images/4.png'),
                                                                                                (5, 'Industrial Bookshelf', 1200.00, 'Modern steel frame', '../images/5.png'),
                                                                                                (6, 'Queen Size Bed Frame', 2200.00, 'Sturdy, timeless design', '../images/6.png');

-- 7. Create the orders table
CREATE TABLE `orders` (
                          `order_id` INT AUTO_INCREMENT PRIMARY KEY,
                          `user_id` INT NOT NULL,
                          `product_id` INT NOT NULL,
                          `quantity` INT NOT NULL DEFAULT 1,
                          `total` DECIMAL(10,2) NOT NULL,
                          `order_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          `status` ENUM('Pending', 'Processing', 'Delivered', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
                          CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                          CONSTRAINT `fk_orders_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Insert a test customer (password = "password")
INSERT INTO `users` (
    `full_name`,
    `email`,
    `password`,
    `phone`,
    `role`,
    `delivery_address`,
    `date_of_birth`
) VALUES (
             'Test Customer',
             'customer@test.com',
             '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
             '+1234567890',
             'customer',
             '123 Main Street, City, Country',
             '1990-01-15'
         );

-- 9. Insert a test staff member (password = "password")
INSERT INTO `users` (
    `full_name`,
    `email`,
    `password`,
    `phone`,
    `role`,
    `id_number`,
    `bank_name`,
    `bank_account`,
    `emergency_contact`,
    `emergency_phone`,
    `certifications`
) VALUES (
             'Test Staff',
             'staff@test.com',
             '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
             '+1987654321',
             'staff',
             'A123456(7)',
             'HSBC Bank',
             '123456789',
             'Jane Doe',
             '+1122334455',
             'Certified Furniture Specialist, OSHA Certified'
         );

-- 10. Insert sample orders for testing
INSERT INTO `orders` (`user_id`, `product_id`, `quantity`, `total`, `status`) VALUES
                                                                                  (1, 1, 2, 900.00, 'Completed'),
                                                                                  (1, 3, 1, 3800.00, 'Pending'),
                                                                                  (1, 5, 1, 1200.00, 'Delivered');

-- 11. Verify the data
SELECT '=== Users ===' AS '';
SELECT `id`, `full_name`, `email`, `role` FROM `users`;

SELECT '=== Products ===' AS '';
SELECT `product_id`, `product_name`, `price` FROM `products`;

SELECT '=== Orders ===' AS '';
SELECT `order_id`, `user_id`, `product_id`, `quantity`, `total`, `status` FROM `orders`;