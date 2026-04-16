ALTER TABLE `users`
  ADD COLUMN `birthday_date` date DEFAULT NULL AFTER `sex`,
  ADD COLUMN `profile_text` text NOT NULL AFTER `birthday_date`,
  ADD COLUMN `notify_comments` tinyint NOT NULL DEFAULT '0' AFTER `profile_text`,
  ADD COLUMN `download_local_retracker` tinyint NOT NULL DEFAULT '1' AFTER `notify_comments`,
  ADD COLUMN `theme_dark` tinyint NOT NULL DEFAULT '0' AFTER `download_local_retracker`;
