-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2025 at 09:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shop_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` int(100) NOT NULL,
  `quantity` int(100) NOT NULL,
  `image` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `name`, `price`, `quantity`, `image`) VALUES
(62, 2, 'To Kill a Mockingbird', 1499, 1, 'mockingbird.jpg'),
(63, 2, 'The Catcher in the Rye', 1299, 14, 'catcher_rye.jpg'),
(64, 1, 'The Great Gatsby', 1299, 14, 'great_gatsby.jpg'),
(65, 1, 'The Catcher in the Rye', 1299, 14, 'catcher_rye.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `number` varchar(12) NOT NULL,
  `message` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message`
--

INSERT INTO `message` (`id`, `user_id`, `name`, `email`, `number`, `message`) VALUES
(9, 1, 'Brown', 'browngreasy@dcpa.net', '1234567890', 'Excited to receive my books. Thanks!');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `number` varchar(12) NOT NULL,
  `email` varchar(100) NOT NULL,
  `method` varchar(50) NOT NULL,
  `address` varchar(500) NOT NULL,
  `total_products` varchar(1000) NOT NULL,
  `total_price` int(100) NOT NULL,
  `placed_on` varchar(50) NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `name`, `number`, `email`, `method`, `address`, `total_products`, `total_price`, `placed_on`, `payment_status`) VALUES
(9, 1, 'Brown', '1234567890', 'browngreasy@dcpa.net', 'card', '123 Elm Street, Springfield, USA', '1984 (1), The Alchemist (2)', 3997, '2025-05-30', 'paid'),
(10, 1, 'Егізбек Тілеміс', '098999999999', 'browngreasy@dcpa.net', 'credit card', 'Kazakhstan', '1984 (1), The Alchemist (2), The Hobbit (1), Pride and Prejudice (1)', 6695, '2025-05-31', 'pending'),
(11, 1, 'Егізбек Тілеміс', '89898989889', 'browngreasy@dcpa.net', 'credit card', 'Kazakhstan', '1984 (1), The Alchemist (2), The Hobbit (1), Pride and Prejudice (1)', 6695, '2025-05-31', 'pending'),
(12, 1, 'Chief', '0789879989', 'browngreasy@dcpa.net', 'credit card', 'Kazakhstan', 'To Kill a Mockingbird (1), 1984 (1)', 2898, '2025-05-31', 'pending'),
(13, 1, 'Chief', '78867767', '508copper@edny.net', 'credit card', 'kenya', 'The Catcher in the Rye (1), Moby-Dick (1)', 2898, '2025-05-31', 'pending'),
(14, 1, 'Bole', '08888799', 'bole@gmail.com', 'credit card', 'Kenya', 'The Catcher in the Rye (1), Moby-Dick (1), Pride and Prejudice (90)', 110808, '2025-06-01', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `payment_id` varchar(255) NOT NULL,
  `order_id` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `payer_name` varchar(100) DEFAULT NULL,
  `payer_email` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL,
  `payment_status` varchar(50) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_id`, `order_id`, `user_id`, `payer_name`, `payer_email`, `amount`, `currency`, `payment_status`, `created`) VALUES
(1, 'pay_683ac995091d4', NULL, 1, '??????? ???????', 'browngreasy@dcpa.net', 6695.00, 'USD', 'pending', '2025-05-31 11:19:17'),
(2, 'pay_683ad2cd83da0', NULL, 1, 'Chief', 'browngreasy@dcpa.net', 2898.00, 'USD', 'pending', '2025-05-31 11:58:37'),
(3, 'pay_683b4248adf33', NULL, 1, 'Chief', '508copper@edny.net', 2898.00, 'USD', 'pending', '2025-05-31 19:54:16'),
(4, 'pay_683bf34e6155a', NULL, 1, 'Bole', 'bole@gmail.com', 110808.00, 'USD', 'pending', '2025-06-01 08:29:34');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` int(100) NOT NULL,
  `image` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `image`) VALUES
(1, 'The Great Gatsby', 1299, 'great_gatsby.jpg'),
(2, 'To Kill a Mockingbird', 1499, 'mockingbird.jpg'),
(3, '1984', 1399, '1984.jpg'),
(4, 'Pride and Prejudice', 1199, 'pride_prejudice.jpg'),
(5, 'The Catcher in the Rye', 1299, 'catcher_rye.jpg'),
(6, 'Moby-Dick', 1599, 'moby_dick.jpg'),
(7, 'War and Peace', 1799, 'war_peace.jpg'),
(8, 'The Hobbit', 1499, 'hobbit.jpg'),
(9, 'Brave New World', 1399, 'brave_new_world.jpg'),
(10, 'The Alchemist', 1299, 'alchemist.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `user_type` varchar(20) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `user_type`) VALUES
(1, 'Brown', 'browngreasy@dcpa.net', '6edf699f21e8ed9ce01814cd241c6292', 'user'),
(2, 'visa', 'bola@gmail.com', '6edf699f21e8ed9ce01814cd241c6292', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_id` (`payment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
