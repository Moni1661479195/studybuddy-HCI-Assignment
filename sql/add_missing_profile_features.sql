-- Add missing profile columns to the users table
ALTER TABLE `users`
ADD COLUMN `date_of_birth` DATE DEFAULT NULL AFTER `bio`,
ADD COLUMN `country` VARCHAR(255) DEFAULT NULL AFTER `date_of_birth`,
ADD COLUMN `major` VARCHAR(255) DEFAULT NULL AFTER `country`;

-- Add missing desired_gender column to the quick_match_queue table
ALTER TABLE `quick_match_queue`
ADD COLUMN `desired_gender` ENUM('male', 'female', 'any') DEFAULT 'any' AFTER `desired_skill_level`;
