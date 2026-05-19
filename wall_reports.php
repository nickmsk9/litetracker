<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Жалобы на комментарии стены профиля
===================================================================
*/

require __DIR__ . '/app/system/init.php';

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

function wall_reports_excerpt($text, $length = 260)
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

function wall_reports_status_label($status)
{
	return ($status === 'resolved' ? 'закрыта' : 'открыта');
}

$tableName = user_wall_reports_table_name();
$status = wall_reports_normalize_status($_GET['status'] ?? 'open');
$done = trim((string) ($_GET['done'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = trim((string) ($_POST['act'] ?? ''));
	$reportId = (int) ($_POST['id'] ?? 0);
	$returnStatus = wall_reports_normalize_status($_POST['status'] ?? $status);

	if (!lt_csrf_validate('wall_reports')) {
		err('Ошибка', 'Сессия устарела. Обновите страницу и повторите действие.', 1);
	}

	if ($reportId <= 0 || ($action !== 'resolve' && $action !== 'reopen')) {
		err('Ошибка', 'Некорректное действие.', 1);
	}

	$report = $db->super_query("SELECT id FROM `".$tableName."` WHERE id = {$reportId} LIMIT 1");
	if (empty($report['id'])) {
		err('Ошибка', 'Жалоба не найдена.', 1);
	}

	if ($action === 'resolve') {
		$db->query("UPDATE `".$tableName."` SET status = 'resolved', resolved_at = NOW(), resolved_by_user_id = ".(int) $USER['id']." WHERE id = {$reportId}");
		header('Location: '.wall_reports_build_href(array('status' => $returnStatus, 'done' => 'resolved')));
		die();
	}

	$db->query("UPDATE `".$tableName."` SET status = 'open', resolved_at = NULL, resolved_by_user_id = 0 WHERE id = {$reportId}");
	header('Location: '.wall_reports_build_href(array('status' => $returnStatus, 'done' => 'reopened')));
	die();
}

$where = '';
$pagerParams = array();
if ($status !== 'all') {
	$where = "WHERE r.status = '".$db->safesql($status)."'";
	$pagerParams['status'] = $status;
} else {
	$pagerParams['status'] = 'all';
}

$countRow = $db->super_query("SELECT COUNT(*) AS cnt FROM `".$tableName."` AS r ".$where);
$countReports = (int) ($countRow['cnt'] ?? 0);
$openRow = $db->super_query("SELECT COUNT(*) AS cnt FROM `".$tableName."` WHERE status = 'open'");
$resolvedRow = $db->super_query("SELECT COUNT(*) AS cnt FROM `".$tableName."` WHERE status = 'resolved'");
$openCount = (int) ($openRow['cnt'] ?? 0);
$resolvedCount = (int) ($resolvedRow['cnt'] ?? 0);

$pagerHref = 'wall_reports.php'.($pagerParams ? '?'.http_build_query($pagerParams).'&' : '?');
list($pagertop, $pagerbottom, $limit) = pager(20, $countReports, $pagerHref, array('lastpagedefault' => 1));

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
?>
<div class="lt-admin-page wall-reports-page">
	<section class="lt-admin-hero">
		<h1>Жалобы на стену</h1>
		<p class="lt-admin-lead">Модерация жалоб на комментарии в профилях: кто пожаловался, на кого, что именно было в комментарии и что уже обработано.</p>
	</section>

	<?php if ($done === 'resolved') { ?>
	<div class="lt-admin-notice lt-admin-notice-success">Жалоба помечена как обработанная.</div>
	<?php } elseif ($done === 'reopened') { ?>
	<div class="lt-admin-notice lt-admin-notice-success">Жалоба снова открыта.</div>
	<?php } ?>

	<section class="lt-admin-panel">
		<div class="lt-admin-grid wall-reports-stats">
			<a class="lt-admin-option wall-report-stat<?=($status === 'open' ? ' is-active' : '');?>" href="<?=wall_reports_build_href(array('status' => 'open'))?>">
				<span class="lt-admin-muted">Открытые</span>
				<strong><?=number_format($openCount);?></strong>
			</a>
			<a class="lt-admin-option wall-report-stat<?=($status === 'resolved' ? ' is-active' : '');?>" href="<?=wall_reports_build_href(array('status' => 'resolved'))?>">
				<span class="lt-admin-muted">Закрытые</span>
				<strong><?=number_format($resolvedCount);?></strong>
			</a>
			<a class="lt-admin-option wall-report-stat<?=($status === 'all' ? ' is-active' : '');?>" href="<?=wall_reports_build_href(array('status' => 'all'))?>">
				<span class="lt-admin-muted">Всего</span>
				<strong><?=number_format($openCount + $resolvedCount);?></strong>
			</a>
		</div>
	</section>

	<section class="lt-admin-panel">
		<h2>Список жалоб</h2>
		<p class="lt-admin-panel-text">Текущий фильтр: <?=htmlspecialchars($status === 'all' ? 'все жалобы' : wall_reports_status_label($status), ENT_QUOTES, 'UTF-8');?>.</p>

		<?php if (!$countReports) { ?>
		<div class="lt-admin-empty">Жалоб в этом разделе нет.</div>
		<?php } else { ?>
		<?=$pagertop;?>
		<div class="lt-admin-table-wrap">
			<table class="lt-admin-table wall-reports-table">
				<thead>
					<tr>
						<th>Дата</th>
						<th>Комментарий</th>
						<th>Участники</th>
						<th>Статус</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($row = $db->get_row($sql)) { ?>
					<?php
					$commentText = wall_reports_excerpt(!empty($row['live_comment_text']) ? $row['live_comment_text'] : ($row['comment_text_snapshot'] ?? ''));
					$profileHref = profile_href((int) $row['object_id']);
					$commentHref = $profileHref.'#wall-comment-'.(int) $row['comment_id'];
					$commentUserName = htmlspecialchars((string) ($row['comment_user_name'] ?? 'Неизвестно'), ENT_QUOTES, 'UTF-8');
					$reporterName = htmlspecialchars((string) ($row['reporter_name'] ?? 'Неизвестно'), ENT_QUOTES, 'UTF-8');
					$rowStatus = (string) ($row['status'] ?? 'open');
					?>
					<tr>
						<td>
							<?=convent_date($row['created_at']);?>
							<div class="lt-admin-muted">#<?=(int) $row['id'];?></div>
						</td>
						<td>
							<div class="wall-report-comment"><?=htmlspecialchars($commentText, ENT_QUOTES, 'UTF-8');?></div>
							<?php if (empty($row['comment_exists'])) { ?>
							<div class="lt-admin-muted">Комментарий уже удалён.</div>
							<?php } ?>
						</td>
						<td>
							<div>Автор: <a href="<?=profile_href((int) $row['comment_user_id']);?>"><?=get_user_color((int) ($row['comment_user_class'] ?? 0), $commentUserName);?></a></div>
							<div>Жалоба: <a href="<?=profile_href((int) $row['reporter_user_id']);?>"><?=get_user_color((int) ($row['reporter_class'] ?? 0), $reporterName);?></a></div>
						</td>
						<td>
							<span class="wall-report-status wall-report-status-<?=htmlspecialchars($rowStatus, ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars(wall_reports_status_label($rowStatus), ENT_QUOTES, 'UTF-8');?></span>
							<?php if (!empty($row['resolved_at'])) { ?>
							<div class="lt-admin-muted">Обработал: <?=htmlspecialchars((string) ($row['resolver_name'] ?? 'неизвестно'), ENT_QUOTES, 'UTF-8');?></div>
							<div class="lt-admin-muted"><?=convent_date($row['resolved_at']);?></div>
							<?php } ?>
						</td>
						<td>
							<div class="lt-admin-inline-actions">
								<a class="lt-admin-link-button lt-admin-button-secondary" href="<?=htmlspecialchars($commentHref, ENT_QUOTES, 'UTF-8');?>">Открыть</a>
								<form method="post" action="wall_reports.php" class="wall-report-action-form">
									<?=lt_csrf_input('wall_reports');?>
									<input type="hidden" name="id" value="<?=(int) $row['id'];?>">
									<input type="hidden" name="status" value="<?=htmlspecialchars($status, ENT_QUOTES, 'UTF-8');?>">
									<?php if ($rowStatus === 'open') { ?>
									<input type="hidden" name="act" value="resolve">
									<button class="lt-admin-button" type="submit">Закрыть</button>
									<?php } else { ?>
									<input type="hidden" name="act" value="reopen">
									<button class="lt-admin-button lt-admin-button-secondary" type="submit">Переоткрыть</button>
									<?php } ?>
								</form>
							</div>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<?=$pagerbottom;?>
		<?php } ?>
	</section>
</div>
<?php
foot();
?>
