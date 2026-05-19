<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Пользователи
===================================================================
*/

require __DIR__ . '/app/system/init.php';

is_login();

if (empty($PRIV['users_view'])) {
	err($language['default_1'], $language['users_19'], 1);
}

function users_sort_options()
{
	return array(
		'desc' => array('label' => 'Новые сначала', 'order' => 'u.added DESC, u.id DESC'),
		'asc' => array('label' => 'Старые сначала', 'order' => 'u.added ASC, u.id ASC'),
		'active' => array('label' => 'Недавно были', 'order' => 'u.last_access DESC, u.id DESC'),
		'name' => array('label' => 'По имени', 'order' => 'u.name ASC, u.id ASC'),
	);
}

function users_format_activity($lastAccess)
{
	$lastAccess = trim((string) $lastAccess);
	if ($lastAccess === '' || $lastAccess === '0000-00-00 00:00:00') {
		return 'неизвестно';
	}

	return convent_date($lastAccess);
}

$search = trim((string) ($_GET['search'] ?? ''));
$classFilter = (int) ($_GET['class'] ?? 0);
$sortKey = trim((string) ($_GET['sort'] ?? 'desc'));
$sortOptions = users_sort_options();
if (empty($sortOptions[$sortKey])) {
	$sortKey = 'desc';
}

$classes = get_classes_list();
$classesById = array();
foreach ($classes as $classRow) {
	$classesById[(int) ($classRow['id'] ?? 0)] = (string) ($classRow['NAME'] ?? '');
}

$where = array();
$pagerParams = array();

if ($search !== '') {
	$where[] = "u.name LIKE '%".sqlwildcardesc($search)."%'";
	$pagerParams['search'] = $search;
}

if ($classFilter > 0) {
	$where[] = 'u.class = '.$classFilter;
	$pagerParams['class'] = $classFilter;
}

$pagerParams['sort'] = $sortKey;
$whereSql = ($where ? 'WHERE '.implode(' AND ', $where) : '');
$bonusColumn = lt_user_bonus_column();

$countRow = $db->super_query("SELECT COUNT(*) AS cnt FROM users AS u ".$whereSql);
$countUsers = (int) ($countRow['cnt'] ?? 0);
$activeFrom = $db->safesql(get_date_time(gmtime() - 300));
$activeRow = $db->super_query("SELECT COUNT(*) AS cnt FROM users AS u WHERE u.last_access >= '".$activeFrom."'");
$activeUsers = (int) ($activeRow['cnt'] ?? 0);

$pagerHref = 'users.php'.($pagerParams ? '?'.http_build_query($pagerParams).'&' : '?');
list($pagertop, $pagerbottom, $limit) = pager(20, $countUsers, $pagerHref);

$usersSql = $db->query(
	"SELECT
		u.id,
		u.name,
		u.class,
		u.avatar,
		u.added,
		u.last_access,
		u.uploaded,
		u.downloaded,
		u.".$bonusColumn." AS bonus_value,
		u.num_messages,
		u.num_friends,
		u.banned
	FROM users AS u
	".$whereSql."
	ORDER BY ".$sortOptions[$sortKey]['order']."
	".$limit
);

head('Пользователи');
?>
<div class="lt-admin-page users-admin-page">
	<section class="lt-admin-hero">
		<h1>Пользователи</h1>
		<p class="lt-admin-lead">Поиск по аккаунтам, быстрый переход в профиль и администрирование пользователей без старых вложенных таблиц.</p>
	</section>

	<section class="lt-admin-panel">
		<form class="lt-admin-form users-admin-filter" action="users.php" method="get">
			<div class="lt-admin-form-grid">
				<div class="lt-admin-field">
					<label class="lt-admin-label" for="users-search">Поиск</label>
					<input id="users-search" class="lt-admin-input" type="text" name="search" value="<?=htmlspecialchars($search, ENT_QUOTES, 'UTF-8');?>" autocomplete="off" placeholder="Ник пользователя">
				</div>
				<div class="lt-admin-field">
					<label class="lt-admin-label" for="users-class">Класс</label>
					<select id="users-class" class="lt-admin-select" name="class">
						<option value="">Все классы</option>
						<?php foreach ($classes as $classRow) { ?>
						<option value="<?=(int) $classRow['id'];?>"<?=($classFilter === (int) $classRow['id'] ? ' selected' : '');?>><?=htmlspecialchars((string) $classRow['NAME'], ENT_QUOTES, 'UTF-8');?></option>
						<?php } ?>
					</select>
				</div>
				<div class="lt-admin-field">
					<label class="lt-admin-label" for="users-sort">Сортировка</label>
					<select id="users-sort" class="lt-admin-select" name="sort">
						<?php foreach ($sortOptions as $key => $option) { ?>
						<option value="<?=htmlspecialchars($key, ENT_QUOTES, 'UTF-8');?>"<?=($sortKey === $key ? ' selected' : '');?>><?=htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8');?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="lt-admin-actions">
				<button class="lt-admin-button" type="submit">Применить</button>
				<a class="lt-admin-link-button lt-admin-button-secondary" href="users.php">Сбросить</a>
				<?php if (!empty($PRIV['user_add'])) { ?>
				<a class="lt-admin-link-button" href="user_add.php">Добавить пользователя</a>
				<?php } ?>
			</div>
		</form>
	</section>

	<section class="lt-admin-panel">
		<div class="lt-admin-grid users-admin-stats">
			<div class="lt-admin-option">
				<div class="lt-admin-muted">Найдено</div>
				<div class="users-admin-stat"><?=number_format($countUsers);?></div>
			</div>
			<div class="lt-admin-option">
				<div class="lt-admin-muted">Активны за 5 минут</div>
				<div class="users-admin-stat"><?=number_format($activeUsers);?></div>
			</div>
			<div class="lt-admin-option">
				<div class="lt-admin-muted">Фильтр класса</div>
				<div class="users-admin-stat users-admin-stat-text"><?=htmlspecialchars($classFilter > 0 ? ($classesById[$classFilter] ?? 'неизвестно') : 'все', ENT_QUOTES, 'UTF-8');?></div>
			</div>
		</div>

		<?php if ($countUsers <= 0) { ?>
		<div class="lt-admin-empty">Пользователи не найдены.</div>
		<?php } else { ?>
		<?=$pagertop;?>
		<div class="lt-admin-table-wrap">
			<table class="lt-admin-table users-admin-table">
				<thead>
					<tr>
						<th>Пользователь</th>
						<th>Класс</th>
						<th>Регистрация</th>
						<th>Активность</th>
						<th>Статистика</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($row = $db->get_row($usersSql)) { ?>
					<?php
					$userId = (int) ($row['id'] ?? 0);
					$userName = htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8');
					$isOnline = ((string) ($row['last_access'] ?? '') >= get_date_time(gmtime() - 50));
					$avatar = trim((string) ($row['avatar'] ?? ''));
					$avatarPath = ($avatar !== '' ? 'public/avatars/'.$avatar : 'public/images/default_avatar.gif');
					?>
					<tr>
						<td>
							<div class="users-admin-user">
								<a class="users-admin-avatar" href="<?=profile_href($userId);?>">
									<img src="<?=htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8');?>" alt="">
								</a>
								<div>
									<a class="users-admin-name" href="<?=profile_href($userId);?>"><?=get_user_color((int) ($row['class'] ?? 0), $userName, $userId);?></a>
									<div class="lt-admin-muted">ID <?=$userId;?><?=(!empty($row['banned']) ? ' · заблокирован' : '');?></div>
								</div>
							</div>
						</td>
						<td><?=get_user_class_name((int) ($row['class'] ?? 0));?></td>
						<td><?=users_format_activity($row['added'] ?? '');?></td>
						<td>
							<span class="users-admin-status<?=($isOnline ? ' users-admin-status-online' : '');?>"><?=($isOnline ? 'онлайн' : 'офлайн');?></span>
							<div class="lt-admin-muted"><?=users_format_activity($row['last_access'] ?? '');?></div>
						</td>
						<td>
							<div>↑ <?=mksize((float) ($row['uploaded'] ?? 0));?></div>
							<div>↓ <?=mksize((float) ($row['downloaded'] ?? 0));?></div>
							<div class="lt-admin-muted">Бонус: <?=number_format((float) ($row['bonus_value'] ?? 0), 2, '.', ' ');?></div>
						</td>
						<td>
							<div class="lt-admin-inline-actions">
								<a class="lt-admin-link-button lt-admin-button-secondary" href="<?=profile_href($userId);?>">Профиль</a>
								<?php if ((int) $USER['id'] !== $userId) { ?>
								<a class="lt-admin-link-button lt-admin-button-secondary" href="my.mail.php?act=conversation&amp;id_user=<?=$userId;?>">Сообщение</a>
								<?php } ?>
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
