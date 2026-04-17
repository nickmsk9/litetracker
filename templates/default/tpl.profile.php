<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}
?>
<style>
.profile-layout {
	display: flex;
	align-items: flex-start;
	gap: 20px;
}

.profile-primary {
	flex: 1 1 auto;
	min-width: 0;
}

.profile-sidebar {
	flex: 0 0 300px;
	width: 300px;
}

.profile-card,
.profile-wall {
	width: 100%;
	box-sizing: border-box;
	background: #fff;
	border: 1px solid #d9e1e8;
	border-radius: 4px;
}

.profile-wall {
	margin-top: 18px;
	overflow: hidden;
}

.profile-wall-header {
	padding: 14px 18px;
	border-bottom: 1px solid #d9e1e8;
}

.profile-wall-title {
	margin: 0;
	font-size: 16px;
	font-weight: 600;
	line-height: 1.35;
}

.profile-wall-body {
	padding: 18px;
	box-sizing: border-box;
}

.wall-comments-list {
	width: 100%;
	box-sizing: border-box;
}

.wall-comment {
	display: flex;
	align-items: flex-start;
	gap: 10px;
	width: 100%;
	margin-bottom: 24px;
}

.wall-comment:last-child {
	margin-bottom: 18px;
}

.wall-comment-avatar {
	flex: 0 0 40px;
	width: 40px;
	display: inline-flex;
	align-items: flex-start;
	justify-content: center;
}

.wall-comment-avatar img {
	display: block;
	width: 40px;
	height: 40px;
	object-fit: cover;
	border-radius: 4px;
}

.wall-comment-body {
	flex: 1 1 auto;
	min-width: 0;
}

.wall-comment-meta {
	margin-bottom: 4px;
	line-height: 1.4;
}

.wall-comment-author {
	font-weight: 600;
	font-size: 15px;
}

.wall-comment-date {
	margin-left: 6px;
	color: #8b98a7;
	font-size: 12px;
	white-space: nowrap;
}

.wall-comment-text {
	margin-bottom: 8px;
	line-height: 1.45;
	word-wrap: break-word;
}

.wall-comment-actions {
	display: flex;
	align-items: center;
	gap: 8px;
	flex-wrap: wrap;
}

.wall-comment-button {
	display: inline-block;
	padding: 3px 9px;
	border: 1px solid #c9d3dd;
	border-radius: 4px;
	background: #f7f9fb;
	color: #7d8792;
	font-size: 12px;
	line-height: 1.2;
	text-decoration: none;
	cursor: pointer;
}

.wall-comment-button:hover {
	background: #eef3f7;
	color: #5f6b77;
}

.wall-comment-empty {
	padding: 4px 0 12px;
	color: #7d8792;
}

.wall-form {
	width: 100%;
	margin-top: 4px;
}

.wall-form-row {
	display: flex;
	align-items: flex-start;
	gap: 10px;
	width: 100%;
}

.wall-form-avatar {
	flex: 0 0 40px;
	width: 40px;
}

.wall-form-avatar img {
	display: block;
	width: 40px;
	height: 40px;
	object-fit: cover;
	border-radius: 4px;
}

.wall-form-body {
	flex: 1 1 auto;
	min-width: 0;
}

.wall-form-textarea {
	display: block;
	width: 100%;
	min-height: 88px;
	padding: 10px 12px;
	box-sizing: border-box;
	border: 1px solid #cdd7e1;
	border-radius: 4px;
	background: #fff;
	resize: vertical;
}

.wall-form-controls {
	margin-top: 10px;
}

.wall-form-submit {
	padding: 7px 14px;
	border: 0;
	border-radius: 4px;
	background: #4b89d0;
	color: #fff;
	font-size: 13px;
	cursor: pointer;
}

.wall-form-submit:hover {
	background: #3f7cc2;
}
</style>
<div class="profile-page">
	<div class="profile-layout">
		<div class="profile-primary">
			<section class="profile-card">
				<div class="profile-card-media">
					<div class="profile-card-avatar">
						<img src="<?=$avatarLarge;?>" alt="<?=$profileName;?>" width="90" height="90">
					</div>
				</div>

				<div class="profile-card-main">
					<h1 class="profile-card-name"><?=$profileName;?></h1>
					<div class="profile-status <?=$profileStatusClass;?>"><?=$profileStatusLabel;?></div>
					<?php if ($profileAbout !== '') { ?>
					<div class="profile-card-text"><?=$profileAbout;?></div>
					<?php } else { ?>
					<div class="profile-card-text">На трекере с <?=$profileSince;?>. Последняя активность: <?=$profileLastAccess;?>.</div>
					<?php } ?>

					<?php if ($primaryAction) { ?>
					<div class="profile-card-actions">
						<a class="<?=$primaryAction['class'];?>" href="<?=$primaryAction['href'];?>"><?=$primaryAction['label'];?></a>
					</div>
					<?php } ?>
				</div>
			</section>

			<section class="profile-wall">
				<div class="profile-wall-header">
					<h2 class="profile-wall-title">Стена пользователя</h2>
				</div>

				<div class="profile-wall-body">
					<?php
					$wallSql = $db->query("SELECT * FROM comments_users WHERE id_users = " . (int)$id . " ORDER BY date ASC", 0);
					$hasWallComments = false;
					?>

					<div class="wall-comments-list">
						<?php while ($wallRow = $db->get_row($wallSql)) { ?>
							<?php
							$hasWallComments = true;
							$wallUser = get_user_info((int)$wallRow['id_user']);
							$wallUserId = isset($wallUser['id']) ? (int)$wallUser['id'] : 0;
							$wallUserName = isset($wallUser['name']) ? (string)$wallUser['name'] : 'Unknown';
							$wallDate = !empty($wallRow['date']) ? convent_date($wallRow['date']) : '';
							$wallAppendEdit = (!empty($wallRow['date_edit']) && $wallRow['date_edit'] !== '0000-00-00 00:00:00')
								? (($language['comments_3'] ?? 'Изменено:') . ' ' . convent_date($wallRow['date_edit']))
								: '';

							$wallAvatarPath = 'public/images/default_avatar.gif';
							if (!empty($wallUser['avatar']) && is_file('public/avatars/small/' . $wallUser['avatar'])) {
								$wallAvatarPath = 'public/avatars/small/' . $wallUser['avatar'];
							}

							$wallText = cleanhtml((string)($wallRow['text'] ?? ''));
							$wallCommentId = isset($wallRow['id']) ? (int)$wallRow['id'] : 0;
							?>
							<article class="wall-comment">
								<a class="wall-comment-avatar" href="<?=$rewrite->encode('profile.php?id=' . $wallUserId);?>">
									<img src="<?=$wallAvatarPath;?>" alt="<?=htmlspecialchars($wallUserName, ENT_QUOTES, 'UTF-8');?>" width="40" height="40">
								</a>

								<div class="wall-comment-body">
									<div class="wall-comment-meta">
										<a class="wall-comment-author" href="<?=$rewrite->encode('profile.php?id=' . $wallUserId);?>">
											<?=htmlspecialchars($wallUserName, ENT_QUOTES, 'UTF-8');?>
										</a>
										<span class="wall-comment-date">
											<?=htmlspecialchars(($wallAppendEdit ? $wallAppendEdit : $wallDate), ENT_QUOTES, 'UTF-8');?>
										</span>
									</div>

									<div class="wall-comment-text"><?=$wallText;?></div>

									<div class="wall-comment-actions">
										<?php if (!empty($USER)) { ?>
											<button class="wall-comment-button" type="button" onclick="return replyWallComment('<?=htmlspecialchars(addslashes($wallUserName), ENT_QUOTES, 'UTF-8');?>');">Ответить</button>
										<?php } ?>

										<?php if (!empty($USER['id']) && ($USER['id'] == $wallUserId || !empty($PRIV['comments_edit']))) { ?>
											<a class="wall-comment-button" href="comments.take.php?type=users&amp;object_id=<?=(int)$id;?>&amp;id_comment=<?=$wallCommentId;?>&amp;act=edit&amp;file=profile.php?"><?=$language['comments_4'];?></a>
										<?php } ?>

										<?php if (!empty($USER['id']) && ($USER['id'] == $wallUserId || !empty($PRIV['comments_delete']))) { ?>
											<a class="wall-comment-button" href="comments.take.php?type=users&amp;object_id=<?=(int)$id;?>&amp;id_comment=<?=$wallCommentId;?>&amp;act=delete&amp;file=profile.php?"><?=$language['comments_5'];?></a>
										<?php } ?>
									</div>
								</div>
							</article>
						<?php } ?>

						<?php if (!$hasWallComments) { ?>
							<div class="wall-comment-empty">На стене пока нет комментариев.</div>
						<?php } ?>
					</div>

					<?php if (!empty($USER) && is_array($USER)) { ?>
						<?php
						$wallFormAvatar = 'public/images/default_avatar.gif';
						if (!empty($USER['avatar']) && is_file('public/avatars/small/' . $USER['avatar'])) {
							$wallFormAvatar = 'public/avatars/small/' . $USER['avatar'];
						}
						?>
						<form class="wall-form" method="post" action="comments.take.php">
							<div class="wall-form-row">
								<div class="wall-form-avatar">
									<img src="<?=$wallFormAvatar;?>" alt="<?=htmlspecialchars((string)$USER['name'], ENT_QUOTES, 'UTF-8');?>" width="40" height="40">
								</div>
								<div class="wall-form-body">
									<textarea class="wall-form-textarea" id="wall-comment-text" name="text"></textarea>
									<div class="wall-form-controls">
										<input class="wall-form-submit" value="Отправить" type="submit">
									</div>
								</div>
							</div>
							<input type="hidden" name="object_id" value="<?=(int)$id;?>">
							<input type="hidden" name="type" value="users">
							<input type="hidden" name="file" value="profile.php?">
							<input type="hidden" name="act" value="add">
						</form>
					<?php } ?>
				</div>
			</section>
		</div>

		<aside class="profile-sidebar">
			<div class="profile-sidebar-nav">
				<?php foreach ($sidebarLinks as $item) { ?>
				<a class="profile-sidebar-link<?=(!empty($item['active']) ? ' profile-sidebar-link-active' : '');?>" href="<?=$item['href'];?>"><?=$item['label'];?></a>
				<?php } ?>
			</div>

			<section class="profile-sidebar-card">
				<h2 class="profile-sidebar-stats-title">Статистика</h2>
				<div class="profile-sidebar-stat-bonus">Бонус: <strong><?=template_format_number($profileStats['voice']);?></strong></div>

				<div class="profile-sidebar-stat-peers">
					<span class="profile-sidebar-stat-peer"><img src="public/images/up.png" alt="" width="10" height="10"> <?=$profileStats['seeders'];?></span>
					<span class="profile-sidebar-stat-divider">|</span>
					<span class="profile-sidebar-stat-peer"><img src="public/images/down.png" alt="" width="10" height="10"> <?=$profileStats['leechers'];?></span>
				</div>

				<div class="profile-sidebar-stat-transfer">
					<div class="profile-sidebar-stat-transfer-item profile-sidebar-stat-transfer-down"><?=$profileStats['downloaded'];?></div>
					<div class="profile-sidebar-stat-transfer-item profile-sidebar-stat-transfer-up"><?=$profileStats['uploaded'];?></div>
				</div>
			</section>
		</aside>
	</div>
</div>
