<?php
#!/usr/bin/env php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Autoclean system
===================================================================
*/

// header("Content-Type: image/gif");

//Подключаем главный системный файл
require 'system/init.autoclean.php';
//Функции для обновления
require 'system/functions/functions.benc.php';

lt_require_cron_access();

ignore_user_abort(true);
set_time_limit(0);

function autoclean_response_gif()
{
	return base64_decode("R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==");
}

$autocleanLock = lt_lock_acquire('autoclean');
if (!$autocleanLock) {
	die(autoclean_response_gif());
}

$bonusColumn = lt_user_bonus_column();
$birthdayDateFormat = '%m-%d';
$birthdayPhpMonthDayFormat = 'm-d';
$defaultBirthdayBonusAmount = 150;
$birthdayMessageSubject = 'С днём рождения!';
$birthdayMessageTemplate = 'Поздравляем с днём рождения! Начислено бонусов: [b]%s[/b].';
$autoPromotionMinAgeDays = 14;
$autoPromotionMinUploaded = 10 * 1024 * 1024 * 1024;
$autoPromotionMinRatio = 1.05;
$autoPromotionMessageSubject = 'Автоматическое повышение';
$autoPromotionMessageTemplate = 'Наши поздравления, вы были авто-повышены до ранга [b]%s[/b].';


//Autoclean system
if((time() - $CRON['autoclean_last']) < $CRON['autoclean_interval']) {
	lt_lock_release($autocleanLock);
	die(autoclean_response_gif());
}


///////////////////////////////////////////////////////////////////
//Очистка пиров
///////////////////////////////////////////////////////////////////
$secs = 30*60; //Количество секунд , через которое пир считается неактивным
$date = $db->safesql(get_date_time(gmtime() - $secs));
$peers = $db->query("DELETE FROM peers  WHERE last_action < '".$date."'");


///////////////////////////////////////////////////////////////////
//Очистка пиров
///////////////////////////////////////////////////////////////////
$torrents = array();
$res = $db->query('SELECT torrent, seeder, SUM(1) AS c FROM peers GROUP BY torrent, seeder');
while ($row = $db->get_row($res)) {
	if ($row['seeder'])
		$key = 'seeders';
	else
		$key = 'leechers';
	$torrents[$row['torrent']][$key] = $row['c'];
}

$peerssql = $db->query("SELECT torrent FROM trackers WHERE tracker='localhost'");
while (list($id) = $db->get_array($peerssql) ) {
	$seeders = (int) ($torrents[$id]['seeders'] ?? 0);
	$leechers = (int) ($torrents[$id]['leechers'] ?? 0);
	$db->query("UPDATE trackers SET seeders = ".$seeders.", leechers = ".$leechers.", lastchecked = ".time()." WHERE torrent = ".(int) $id." AND tracker='localhost'");
}

///////////////////////////////////////////////////////////////////
//Начисление бонусов
///////////////////////////////////////////////////////////////////
// Начисляем бонусы за сидирование или активное присутствие за последний интервал очистки
$bonus_source = (string) ($config['bonus_source'] ?? 'seeding');
$bonus_per_hour = (float) ($config['bonus_price'] ?? $config['voice_price'] ?? 0);
$bonus_per_cleanup = round($bonus_per_hour * ((int) $CRON['autoclean_interval'] / 3600), 2);
if ($bonus_per_cleanup > 0) {
	if ($bonus_source === 'online') {
		$active_from = $db->safesql(get_date_time(time() - (int) $CRON['autoclean_interval']));
		$active_users = $db->query("SELECT DISTINCT user_id FROM sessions WHERE user_id > 0 AND last_access >= '".$active_from."'");
	} else {
		$active_users = $db->query("SELECT DISTINCT userid AS user_id FROM peers WHERE seeder = 1 AND userid > 0");
	}

	while ($active_user = $db->get_row($active_users)) {
		$db->query("UPDATE users SET {$bonusColumn} = ({$bonusColumn} + ".$bonus_per_cleanup.") WHERE id = ".(int) $active_user['user_id']);
		lt_cache_invalidate_user((int) $active_user['user_id']);
	}
}

///////////////////////////////////////////////////////////////////
//Автопоздравления с днем рождения + бонусы
///////////////////////////////////////////////////////////////////
$birthdayBonusAmount = (float) ($config['birthday_bonus_amount'] ?? $defaultBirthdayBonusAmount);
if ($birthdayBonusAmount > 0) {
	$db->query(
		"CREATE TABLE IF NOT EXISTS `birthday_rewards` (
			`id` int NOT NULL AUTO_INCREMENT,
			`user_id` int NOT NULL,
			`reward_year` int NOT NULL,
			`created_at` datetime NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `user_year` (`user_id`, `reward_year`),
			KEY `reward_year` (`reward_year`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin"
	);

	$currentYear = (int) date('Y');
	$todayMonthDay = date($birthdayPhpMonthDayFormat);
	$birthdayUsers = $db->query(
		"SELECT id, name
		 FROM users
		 WHERE birthday_date IS NOT NULL
		   AND birthday_date > '1000-01-01'
		   AND DATE_FORMAT(birthday_date, '".$db->safesql($birthdayDateFormat)."') = '".$db->safesql($todayMonthDay)."'"
	);
	while ($birthdayUser = $db->get_row($birthdayUsers)) {
		$userId = (int) ($birthdayUser['id'] ?? 0);
		if ($userId <= 0) {
			continue;
		}

		$alreadyRewarded = $db->super_query("SELECT id FROM birthday_rewards WHERE user_id = ".$userId." AND reward_year = ".$currentYear." LIMIT 1");
		if (!empty($alreadyRewarded['id'])) {
			continue;
		}

		$db->query("UPDATE users SET {$bonusColumn} = ({$bonusColumn} + ".$birthdayBonusAmount.") WHERE id = ".$userId);
		$db->query("INSERT INTO birthday_rewards (user_id, reward_year, created_at) VALUES (".$userId.", ".$currentYear.", NOW())");
		send_msg($birthdayMessageSubject, sprintf($birthdayMessageTemplate, number_format($birthdayBonusAmount, 0, '.', ' ')), $userId, 0);
		$memcached->delete('user_'.$userId);
	}
}

///////////////////////////////////////////////////////////////////
//Автоповышение пользователей
///////////////////////////////////////////////////////////////////
$signupClass = $db->super_query("SELECT id FROM priv WHERE SIGNUP = 1 ORDER BY id ASC LIMIT 1");
$signupClassId = (int) ($signupClass['id'] ?? 0);

if ($signupClassId > 0) {
	$autoPromotionTarget = $db->super_query(
		"SELECT id, NAME
		 FROM priv
		 WHERE id <> ".$signupClassId."
		   AND upload = 1
		   AND download_torrent = 1
		   AND download_magnet = 1
		   AND comments_edit = 0
		   AND comments_delete = 0
		   AND setting_user = 0
		   AND EDIT_PRIV = 0
		   AND users_view = 0
		   AND user_add = 0
		 ORDER BY id ASC
		 LIMIT 1"
	);
	$autoPromotionTargetId = (int) ($autoPromotionTarget['id'] ?? 0);

	if ($autoPromotionTargetId > 0) {
		$db->query(
			"CREATE TABLE IF NOT EXISTS `user_auto_promotions` (
				`id` int unsigned NOT NULL AUTO_INCREMENT,
				`user_id` int unsigned NOT NULL,
				`from_class` int unsigned NOT NULL,
				`to_class` int unsigned NOT NULL,
				`created_at` datetime NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `user_to_class` (`user_id`, `to_class`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin"
		);

		$eligibleUsers = $db->query(
			"SELECT id, uploaded, downloaded
			 FROM users
			 WHERE class = ".$signupClassId."
			   AND banned = 0
			   AND confirm = 1
			   AND bad_rating = 0
			   AND added <= DATE_SUB(NOW(), INTERVAL ".$autoPromotionMinAgeDays." DAY)
			   AND uploaded >= ".$autoPromotionMinUploaded
		);

		while ($promotionUser = $db->get_row($eligibleUsers)) {
			$promotionUserId = (int) ($promotionUser['id'] ?? 0);
			if ($promotionUserId <= 0) {
				continue;
			}

			$downloaded = (int) ($promotionUser['downloaded'] ?? 0);
			$uploaded = (int) ($promotionUser['uploaded'] ?? 0);
			$ratio = ($downloaded > 0 ? ($uploaded / $downloaded) : $autoPromotionMinRatio);
			if ($ratio < $autoPromotionMinRatio) {
				continue;
			}

			$alreadyPromoted = $db->super_query("SELECT id FROM user_auto_promotions WHERE user_id = ".$promotionUserId." AND to_class = ".$autoPromotionTargetId." LIMIT 1");
			if (!empty($alreadyPromoted['id'])) {
				continue;
			}

			$db->query("UPDATE users SET class = ".$autoPromotionTargetId." WHERE id = ".$promotionUserId." AND class = ".$signupClassId);
			if ($db->affected_rows() > 0) {
				$db->query("INSERT INTO user_auto_promotions (user_id, from_class, to_class, created_at) VALUES (".$promotionUserId.", ".$signupClassId.", ".$autoPromotionTargetId.", NOW())");
				if (lt_table_exists('user_admin_notes')) {
					$db->query("INSERT INTO user_admin_notes (user_id, admin_id, note, created_at) VALUES (".$promotionUserId.", 0, 'Автоповышение: ".(int) $signupClassId." -> ".$db->safesql((string) $autoPromotionTarget['NAME'])."', NOW())");
				}
				send_msg($autoPromotionMessageSubject, sprintf($autoPromotionMessageTemplate, $autoPromotionTarget['NAME']), $promotionUserId, 0);
				lt_cache_invalidate_user($promotionUserId);
			}
		}
	}
}


///////////////////////////////////////////////////////////////////
//Удаление просроченный кодов "Забыли пароль?"
///////////////////////////////////////////////////////////////////
$secs = 15*24*(60*60); //Количество секунд , через которое код считается просроченным (15 дней)
$date = $db->safesql(get_date_time(gmtime() - $secs));
$sql = $db->query("SELECT * FROM forgot  WHERE date < '".$date."'");
while($arr = $db->get_row($sql) ) {
	$db->query("DELETE FROM forgot WHERE id = ".$arr['id']);

}

//Обновляем cron-запись
$db->query("UPDATE cron SET cron_value=".time()." WHERE cron_name='autoclean_last'");
lt_cache_invalidate_cron();
lt_lock_release($autocleanLock);
die(autoclean_response_gif());
?>
