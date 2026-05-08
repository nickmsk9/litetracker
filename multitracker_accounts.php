<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Отлов мультитрекерных аккаунтов (by webnet)
===================================================================
*/

require 'system/init.php';

is_login();

if (empty($PRIV['multitracker_accounts'])) {
	err('Ошибка', 'Доступ закрыт', 1);
}

$torrentNames = array();
$torrentSql = $db->query("SELECT id, name FROM torrents");
while ($torrentRow = $db->get_row($torrentSql)) {
	$torrentNames[(int) $torrentRow['id']] = (string) $torrentRow['name'];
}
$db->free($torrentSql);

$accounts = array();
$sql = $db->query("SELECT
					  p.userid,
					  COALESCE(u.name, '') AS user_name,
					  GROUP_CONCAT(DISTINCT p.torrent ORDER BY p.torrent ASC) AS torrents,
					  GROUP_CONCAT(DISTINCT p.ip ORDER BY p.ip ASC) AS ips,
					  COUNT(DISTINCT p.torrent) AS torrent_count,
					  COUNT(DISTINCT p.ip) AS ip_count
				  FROM peers p
				  LEFT JOIN users u ON p.userid = u.id
				  GROUP BY p.userid, u.name
				  HAVING ip_count > 1 AND torrent_count < 5
				  ORDER BY ip_count DESC, torrent_count ASC, p.userid ASC");

while ($row = $db->get_row($sql)) {
	$ips = array_filter(array_map('trim', explode(',', (string) ($row['ips'] ?? ''))));
	$torrents = array_filter(array_map('intval', explode(',', (string) ($row['torrents'] ?? ''))));

	if (!$ips || !$torrents) {
		continue;
	}

	$accounts[] = array(
		'id' => (int) ($row['userid'] ?? 0),
		'name' => (string) ($row['user_name'] ?? ''),
		'ips' => $ips,
		'torrents' => $torrents,
	);
}
$db->free($sql);

head('Мультитрекерные аккаунты');
?>
<div class="lt-admin-page">
	<section class="lt-admin-hero">
		<h1>Мультитрекерные аккаунты</h1>
		<p class="lt-admin-lead">Список показывает пользователей, которые раздают с нескольких IP и при этом замечены на небольшом числе торрентов. Это не бан-лист, а подсказка для ручной проверки.</p>
		<div class="lt-admin-actions">
			<a class="lt-admin-link-button lt-admin-button-secondary" href="admin.php?tab=moderation">Назад в админку</a>
		</div>
	</section>

	<section class="lt-admin-panel">
		<h2>Найдено: <?=number_format(count($accounts));?></h2>
		<p class="lt-admin-panel-text">Пример проверки: открыть IP, сравнить активность и только после этого принимать решение. Совпадение IP само по себе не доказывает нарушение.</p>

		<?php if (!$accounts) { ?>
		<div class="lt-admin-empty" style="margin-top:14px;">Подозрительных сочетаний сейчас нет.</div>
		<?php } else { ?>
		<div class="lt-admin-table-wrap lt-table-scroll">
			<table class="lt-table lt-table-compact lt-admin-table">
				<thead>
					<tr>
						<th>Пользователь</th>
						<th>IP-адреса</th>
						<th>Торренты</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($accounts as $account) { ?>
					<tr>
						<td>
							<?php if ($account['id'] > 0) { ?>
							<a href="<?=profile_href($account['id']);?>"><?=htmlspecialchars($account['name'] !== '' ? $account['name'] : 'Пользователь #'.$account['id'], ENT_QUOTES, 'UTF-8');?></a>
							<?php } else { ?>
							<span class="lt-admin-muted">Гость</span>
							<?php } ?>
						</td>
						<td>
							<?php foreach ($account['ips'] as $ip) { ?>
							<a href="ip.util.php?ip=<?=urlencode($ip);?>"><?=htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');?></a><br>
							<?php } ?>
						</td>
						<td>
							<?php foreach ($account['torrents'] as $torrentId) { ?>
							<a target="_blank" href="details.php?id=<?=$torrentId;?>&amp;dllist=1#seeders"><?=htmlspecialchars($torrentNames[$torrentId] ?? 'Торрент #'.$torrentId, ENT_QUOTES, 'UTF-8');?></a><br>
							<?php } ?>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<?php } ?>
	</section>
</div>
<?php
foot();
