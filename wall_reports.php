<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Жалобы на комментарии стены профиля
===================================================================
*/

require 'system/init.php';

is_login();

if (!user_wall_reports_can_moderate()) {
	err('Ошибка', 'Недостаточно прав для просмотра жалоб.', 1);
}

user_wall_reports_ensure_table();

function wall_reports_normalize_status($status)
{
	$status = trim((string) $status);
	$allowed = array('open', 'resolved', 'all');

	return (in_array($status, $allowed, true) ? $status : 'open');
}

function wall_reports_excerpt($text, $length = 220)
{
	$text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));
	if ($text === '') {
		return 'Текст комментария отсутствует.';
	}

	if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $length) {
		return rtrim(mb_substr($text, 0, $length, 'UTF-8')).'...';
	}

	return $text;
}

function wall_reports_build_href($params = array())
{
	$params = (is_array($params) ? array_filter($params, static function ($value) {
		return !($value === null || $value === '');
	}) : array());

	return 'wall_reports.php'.($params ? '?'.http_build_query($params) : '');
}

$tableName = user_wall_reports_table_name();
$status = wall_reports_normalize_status($_GET['status'] ?? 'open');
$action = trim((string) ($_GET['act'] ?? ''));
$reportId = (int) ($_GET['id'] ?? 0);

if ($reportId > 0 && ($action === 'resolve' || $action === 'reopen')) {
	$report = $db->super_query("SELECT id FROM `".$tableName."` WHERE id = {$reportId} LIMIT 1");
	if (empty($report['id'])) {
		err('Ошибка', 'Жалоба не найдена.', 1);
	}

	if ($action === 'resolve') {
		$db->query("UPDATE `".$tableName."` SET status = 'resolved', resolved_at = NOW(), resolved_by_user_id = ".(int) $USER['id']." WHERE id = {$reportId}");
		header('Location: '.wall_reports_build_href(array('done' => 'resolved')));
		die();
	}

	$db->query("UPDATE `".$tableName."` SET status = 'open', resolved_at = NULL, resolved_by_user_id = 0 WHERE id = {$reportId}");
	header('Location: '.wall_reports_build_href(array('status' => 'resolved', 'done' => 'reopened')));
	die();
}

$where = '';
$get = array();
if ($status !== 'all') {
	$where = "WHERE r.status = '".$db->safesql($status)."'";
	$get[] = 'status='.$status;
} elseif ($status === 'all') {
	$get[] = 'status=all';
}

$countRow = $db->super_query("SELECT COUNT(*) AS cnt FROM `".$tableName."` AS r ".$where);
$countReports = (int) ($countRow['cnt'] ?? 0);
list($pagertop, $pagerbottom, $limit) = pager('20', $countReports, 'wall_reports.php?'.(count($get) ? implode('&', $get).'&' : ''), array('lastpagedefault' => 1));

$sql = $db->query(
	"SELECT
		r.*,
		c.id AS comment_exists,
		c.text AS live_comment_text,
		reporter.name AS reporter_name,
		reporter.class AS reporter_class,
		comment_user.name AS comment_user_name,
		comment_user.class AS comment_user_class,
		resolver.name AS resolver_name
	FROM `".$tableName."` AS r
	LEFT JOIN comments_users AS c ON c.id = r.comment_id AND c.id_users = r.object_id
	LEFT JOIN users AS reporter ON reporter.id = r.reporter_user_id
	LEFT JOIN users AS comment_user ON comment_user.id = r.comment_user_id
	LEFT JOIN users AS resolver ON resolver.id = r.resolved_by_user_id
	".$where."
	ORDER BY r.created_at DESC, r.id DESC
	".$limit
);

head('Жалобы на стену');

begin_frame('Жалобы на комментарии стены');
?>
<div style="margin-bottom: 14px;">
	<a href="<?=user_wall_reports_href('open');?>">Открытые</a> |
	<a href="<?=user_wall_reports_href('resolved');?>">Закрытые</a> |
	<a href="<?=user_wall_reports_href('all');?>">Все</a>
</div>

<?php
$done = trim((string) ($_GET['done'] ?? ''));
if ($done === 'resolved') {
	msg('Успешно', 'Жалоба помечена как обработанная.');
} elseif ($done === 'reopened') {
	msg('Успешно', 'Жалоба снова открыта.');
}

if (!$countReports) {
	msg('Информация', 'Жалоб пока нет.');
	end_frame();
	foot();
	die();
}

echo $pagertop;
?>
<table width="95%" align="center" cellpadding="6" cellspacing="0">
	<tr>
		<td><b>Дата</b></td>
		<td><b>Автор комментария</b></td>
		<td><b>Кто пожаловался</b></td>
		<td><b>Комментарий</b></td>
		<td><b>Действия</b></td>
	</tr>
	<?php while ($row = $db->get_row($sql)) { ?>
	<?php
	$commentText = wall_reports_excerpt(!empty($row['live_comment_text']) ? $row['live_comment_text'] : ($row['comment_text_snapshot'] ?? ''));
	$profileHref = profile_href((int) $row['object_id']);
	$commentHref = $profileHref.'#wall-comment-'.(int) $row['comment_id'];
	$commentUserName = htmlspecialchars((string) ($row['comment_user_name'] ?? 'Неизвестно'), ENT_QUOTES, 'UTF-8');
	$reporterName = htmlspecialchars((string) ($row['reporter_name'] ?? 'Неизвестно'), ENT_QUOTES, 'UTF-8');
	?>
	<tr>
		<td valign="top"><?=convent_date($row['created_at']);?></td>
		<td valign="top"><a href="<?=profile_href((int) $row['comment_user_id']);?>"><?=get_user_color((int) ($row['comment_user_class'] ?? 0), $commentUserName);?></a></td>
		<td valign="top"><a href="<?=profile_href((int) $row['reporter_user_id']);?>"><?=get_user_color((int) ($row['reporter_class'] ?? 0), $reporterName);?></a></td>
		<td valign="top">
			<div><?=htmlspecialchars($commentText, ENT_QUOTES, 'UTF-8');?></div>
			<?php if (empty($row['comment_exists'])) { ?>
			<small>Комментарий уже удалён.</small>
			<?php } elseif (!empty($row['resolved_at'])) { ?>
			<small>Обработал: <?=htmlspecialchars((string) ($row['resolver_name'] ?? 'неизвестно'), ENT_QUOTES, 'UTF-8');?>, <?=convent_date($row['resolved_at']);?></small>
			<?php } ?>
		</td>
		<td valign="top">
			<input type="button" value="Открыть комментарий" onClick="window.location.href='<?=htmlspecialchars($commentHref, ENT_QUOTES, 'UTF-8');?>'">
			<?php if (($row['status'] ?? 'open') === 'open') { ?>
			<input type="button" value="Закрыть жалобу" onClick="window.location.href='wall_reports.php?act=resolve&id=<?=(int) $row['id'];?>'">
			<?php } else { ?>
			<input type="button" value="Переоткрыть" onClick="window.location.href='wall_reports.php?act=reopen&id=<?=(int) $row['id'];?>'">
			<?php } ?>
		</td>
	</tr>
	<?php } ?>
</table>
<?php
echo $pagerbottom;

end_frame();
foot();
?>
