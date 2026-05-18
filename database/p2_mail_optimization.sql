-- LiteTracker Wave P2 mail optimization indexes.
-- Duplicate-safe: each index is added only when it is absent.
-- Rollback examples:
--   DROP INDEX `idx_mail_in_visible_read_date_id` ON `mail`;
--   DROP INDEX `idx_mail_out_visible_date_id` ON `mail`;
--   DROP INDEX `idx_mail_in_out_visible_date_id` ON `mail`;
--   DROP INDEX `idx_mail_out_in_visible_date_id` ON `mail`;

DELIMITER //

DROP PROCEDURE IF EXISTS lt_add_mail_index_if_missing//
CREATE PROCEDURE lt_add_mail_index_if_missing(
	IN p_index_name VARCHAR(128),
	IN p_index_sql TEXT
)
BEGIN
	IF NOT EXISTS (
		SELECT 1
		FROM information_schema.statistics
		WHERE table_schema = DATABASE()
		  AND table_name = 'mail'
		  AND index_name = p_index_name
		LIMIT 1
	) THEN
		SET @lt_sql = p_index_sql;
		PREPARE lt_stmt FROM @lt_sql;
		EXECUTE lt_stmt;
		DEALLOCATE PREPARE lt_stmt;
	END IF;
END//

CALL lt_add_mail_index_if_missing(
	'idx_mail_in_visible_read_date_id',
	'ALTER TABLE `mail` ADD KEY `idx_mail_in_visible_read_date_id` (`id_user_in`, `delete_in`, `reading`, `date`, `id`)'
)//

CALL lt_add_mail_index_if_missing(
	'idx_mail_out_visible_date_id',
	'ALTER TABLE `mail` ADD KEY `idx_mail_out_visible_date_id` (`id_user_out`, `delete_out`, `date`, `id`)'
)//

CALL lt_add_mail_index_if_missing(
	'idx_mail_in_out_visible_date_id',
	'ALTER TABLE `mail` ADD KEY `idx_mail_in_out_visible_date_id` (`id_user_in`, `id_user_out`, `delete_in`, `date`, `id`)'
)//

CALL lt_add_mail_index_if_missing(
	'idx_mail_out_in_visible_date_id',
	'ALTER TABLE `mail` ADD KEY `idx_mail_out_in_visible_date_id` (`id_user_out`, `id_user_in`, `delete_out`, `date`, `id`)'
)//

DROP PROCEDURE IF EXISTS lt_add_mail_index_if_missing//

DELIMITER ;
