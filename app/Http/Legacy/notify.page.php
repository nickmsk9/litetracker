<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Системные уведомления
===================================================================
*/

require dirname(__DIR__, 3) . '/app/system/init.php';
require_once dirname(__DIR__, 2) . '/system/functions/functions.comments.php';

is_login();

if (user_wall_reports_can_moderate()) {
	user_wall_reports_ensure_table();
	$openReports = $db->super_query("SELECT COUNT(*) AS c FROM `".user_wall_reports_table_name()."` WHERE status = 'open'");
	if ((int) ($openReports['c'] ?? 0) > 0) {
		header('Location: '.user_wall_reports_href());
		die();
	}
}

header('Location: my.mail.php?act=conversation&system=1');
die();
?>
