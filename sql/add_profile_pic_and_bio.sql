ALTER TABLE `users` 
ADD COLUMN `profile_picture_path` VARCHAR(255) NULL DEFAULT NULL AFTER `last_name`,
ADD COLUMN `bio` TEXT NULL DEFAULT NULL AFTER `profile_picture_path`;
