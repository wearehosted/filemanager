ALTER TABLE `users` ADD COLUMN `ldap` BOOLEAN NOT NULL DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `hide_uploads` BOOLEAN NOT NULL DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `copy_raw` BOOLEAN NOT NULL DEFAULT 0;