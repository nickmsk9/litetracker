-- LiteTracker Wave P1 safe additive indexes.
-- Apply manually during a low-traffic window. All changes are reversible with
-- DROP INDEX and do not change data shape or announce protocol.

ALTER TABLE `mail`
  ADD KEY `idx_mail_in_visible_read_date` (`id_user_in`, `delete_in`, `reading`, `date`),
  ADD KEY `idx_mail_out_visible_date` (`id_user_out`, `delete_out`, `date`),
  ADD KEY `idx_mail_conversation_in` (`id_user_in`, `id_user_out`, `delete_in`, `date`, `id`),
  ADD KEY `idx_mail_conversation_out` (`id_user_out`, `id_user_in`, `delete_out`, `date`, `id`);

ALTER TABLE `comments_faq`
  ADD KEY `idx_comments_faq_object_date` (`id_faq`, `date`, `id`),
  ADD KEY `idx_comments_faq_parent` (`parent_id`);

ALTER TABLE `comments_news`
  ADD KEY `idx_comments_news_object_date` (`id_news`, `date`, `id`),
  ADD KEY `idx_comments_news_parent` (`parent_id`);

ALTER TABLE `comments_users`
  ADD KEY `idx_comments_users_object_date` (`id_users`, `date`, `id`);

ALTER TABLE `notifications`
  ADD KEY `idx_notifications_user_archive_id` (`user_id`, `is_archived`, `id`),
  ADD KEY `idx_notifications_user_archive_read_id` (`user_id`, `is_archived`, `is_read`, `id`);

ALTER TABLE `search_query`
  ADD KEY `idx_search_query_last` (`last_date`),
  ADD KEY `idx_search_query_text_prefix` (`text`(191));
