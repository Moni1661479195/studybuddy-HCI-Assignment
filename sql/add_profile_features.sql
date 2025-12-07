-- Add new columns to the users table for enhanced profiles
ALTER TABLE `users`
ADD COLUMN `gender` ENUM('male', 'female', 'other', 'prefer_not_to_say') DEFAULT 'prefer_not_to_say' AFTER `skill_level`,
ADD COLUMN `bio` TEXT AFTER `gender`,
ADD COLUMN `date_of_birth` DATE DEFAULT NULL AFTER `bio`,
ADD COLUMN `country` VARCHAR(255) DEFAULT NULL AFTER `date_of_birth`,
ADD COLUMN `major` VARCHAR(255) DEFAULT NULL AFTER `country`;

-- Add a column to the quick_match_queue to store gender preference
ALTER TABLE `quick_match_queue`
ADD COLUMN `desired_gender` ENUM('male', 'female', 'any') DEFAULT 'any' AFTER `desired_skill_level`;
