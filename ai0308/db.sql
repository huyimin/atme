CREATE TABLE `ai` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ask` text,
  `ans` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);