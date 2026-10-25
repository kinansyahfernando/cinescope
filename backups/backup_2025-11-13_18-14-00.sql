-- Movie Review Database Backup
-- Date: 2025-11-13 18:14:00

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;



-- --------------------------------------------------------
-- Table structure for table `movies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `movies`;
CREATE TABLE `movies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `original_title` varchar(200) DEFAULT NULL,
  `release_year` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'durasi dalam menit',
  `genre` varchar(100) DEFAULT NULL,
  `director` varchar(100) DEFAULT NULL,
  `cast` text DEFAULT NULL COMMENT 'JSON array atau comma separated',
  `synopsis` text DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `trailer_url` varchar(255) DEFAULT NULL,
  `average_rating` decimal(3,1) DEFAULT 0.0,
  `total_reviews` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_title` (`title`),
  KEY `idx_genre` (`genre`),
  KEY `idx_year` (`release_year`),
  CONSTRAINT `movies_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `movies`

INSERT INTO `movies` VALUES ('1', 'Inception', 'Inception', '2010', '148', 'Sci-Fi, Thriller', 'Christopher Nolan', NULL, 'A thief who steals corporate secrets through the use of dream-sharing technology.', NULL, NULL, '0.0', '0', '1', '2025-11-13 23:21:35', '2025-11-13 23:21:35');
INSERT INTO `movies` VALUES ('2', 'The Shawshank Redemption', 'The Shawshank Redemption', '1994', '142', 'Drama', 'Frank Darabont', NULL, 'Two imprisoned men bond over years, finding solace and redemption.', NULL, NULL, '0.0', '0', '1', '2025-11-13 23:21:35', '2025-11-13 23:21:35');
INSERT INTO `movies` VALUES ('3', 'The Dark Knight', 'The Dark Knight', '2008', '152', 'Action, Crime, Drama', 'Christopher Nolan', NULL, 'Batman faces the Joker in a battle for Gotham City.', NULL, NULL, '0.0', '0', '1', '2025-11-13 23:21:35', '2025-11-13 23:21:35');
INSERT INTO `movies` VALUES ('4', 'Pulp Fiction', 'Pulp Fiction', '1994', '154', 'Crime, Drama', 'Quentin Tarantino', NULL, 'Various interconnected stories of crime in Los Angeles.', NULL, NULL, '0.0', '0', '1', '2025-11-13 23:21:35', '2025-11-13 23:21:35');
INSERT INTO `movies` VALUES ('5', 'Parasite', '기생충', '2019', '132', 'Drama, Thriller', 'Bong Joon-ho', NULL, 'A poor family schemes to become employed by a wealthy family.', NULL, NULL, '0.0', '0', '1', '2025-11-13 23:21:35', '2025-11-13 23:21:35');



-- --------------------------------------------------------
-- Table structure for table `review_helpful`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `review_helpful`;
CREATE TABLE `review_helpful` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_review` (`user_id`,`review_id`),
  KEY `review_id` (`review_id`),
  CONSTRAINT `review_helpful_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_helpful_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
-- Table structure for table `reviews`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL CHECK (`rating` >= 0 and `rating` <= 10),
  `review_text` text DEFAULT NULL,
  `is_spoiler` tinyint(1) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_movie` (`user_id`,`movie_id`),
  KEY `idx_movie` (`movie_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
-- Table structure for table `sessions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users`

INSERT INTO `users` VALUES ('1', 'admin', 'admin@moviereview.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, NULL, 'admin', '2025-11-13 23:21:35', '2025-11-13 23:21:35');



-- --------------------------------------------------------
-- Table structure for table `watchlist`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `watchlist`;
CREATE TABLE `watchlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_movie` (`user_id`,`movie_id`),
  KEY `movie_id` (`movie_id`),
  CONSTRAINT `watchlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `watchlist_ibfk_2` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
