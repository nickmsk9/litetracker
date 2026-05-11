<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}
?>
<div class="profile-page" data-profile-page="1" data-profile-user-id="<?=$id;?>">
	<?php if (!empty($profileFlashMessage['text'])) { ?>
	<div class="profile-inline-message profile-inline-message-<?=($profileFlashMessage['type'] === 'error' ? 'error' : 'success');?>">
		<?=htmlspecialchars((string) $profileFlashMessage['text'], ENT_QUOTES, 'UTF-8');?>
	</div>
	<?php } ?>

	<div class="profile-inline-message" id="profile-ajax-message" hidden></div>

	<div class="profile-layout <?=($isOwnProfile ? 'profile-layout-own' : 'profile-layout-foreign');?>">
		<div class="profile-primary">
			<section class="profile-card">
				<div class="profile-card-media">
					<div class="profile-card-avatar">
						<img src="<?=$avatarLarge;?>" alt="<?=$profileName;?>" width="90" height="90">
					</div>
				</div>

				<div class="profile-card-main">
					<h1 class="profile-card-name" data-profile-display-name="1"><?=get_user_color((int) ($arr['class'] ?? 0), $profileName, $arr);?></h1>
					<div class="profile-status <?=$profileStatusClass;?>"><?=$profileStatusLabel;?></div>
					<div class="profile-rank-line">Класс: <strong data-profile-display-class="1"><?=get_user_class_name((int) ($arr['class'] ?? 0));?></strong></div>

					<?php if ($isOwnProfile && $profileAbout !== '') { ?>
					<div class="profile-card-text"><?=$profileAbout;?></div>
					<?php } ?>

					<?php if ($profileActions) { ?>
					<div class="profile-card-actions">
						<?php foreach ($profileActions as $action) { ?>
							<?php if ($action['type'] === 'link') { ?>
							<a class="<?=$action['class'];?>" href="<?=$action['href'];?>"><?=$action['label'];?></a>
							<?php } else { ?>
							<button class="<?=$action['class'];?>" type="button"<?=$action['attributes'] ?? '';?>><?=$action['label'];?></button>
							<?php } ?>
						<?php } ?>
					</div>
					<?php } ?>
				</div>
			</section>

			<?php if ($profileView === 'profile') { ?>
			<?php if (!empty($canManageThisProfile)) { ?>
			<section class="profile-editor-panel" id="profile-editor-panel" hidden>
				<div class="profile-editor-header">
					<h2 class="profile-editor-title">Редактирование пользователя</h2>
					<button class="profile-editor-close" type="button" data-profile-close-editor="1" onclick="var p=document.getElementById('profile-editor-panel');if(p){p.hidden=true;p.setAttribute('hidden','hidden');}var b=document.querySelector('[data-profile-toggle-editor]');if(b){b.setAttribute('aria-expanded','false');}return false;">Закрыть</button>
				</div>

				<form class="profile-editor-form" id="profile-editor-form" method="post" action="ajax/profile.php">
					<div class="profile-editor-grid">
						<div class="settings-field">
							<label class="settings-field-label" for="profile_editor_name">Ник</label>
							<input id="profile_editor_name" type="text" name="name" value="<?=htmlspecialchars((string) ($arr['name'] ?? ''), ENT_QUOTES, 'UTF-8');?>" maxlength="12">
						</div>
						<div class="settings-field">
							<label class="settings-field-label" for="profile_editor_email">E-mail</label>
							<input id="profile_editor_email" type="email" name="email" value="<?=htmlspecialchars((string) ($arr['email'] ?? ''), ENT_QUOTES, 'UTF-8');?>" maxlength="200">
						</div>
						<div class="settings-field">
							<label class="settings-field-label" for="profile_editor_class">Класс</label>
							<select id="profile_editor_class" name="class">
								<?php foreach ($profileEditorClassOptions as $classRow) { ?>
								<option value="<?=(int) $classRow['id'];?>"<?=((int) ($arr['class'] ?? 0) === (int) $classRow['id'] ? ' selected' : '');?>><?=htmlspecialchars((string) $classRow['NAME'], ENT_QUOTES, 'UTF-8');?></option>
								<?php } ?>
							</select>
						</div>
						<div class="settings-field">
							<label class="settings-field-label" for="profile_editor_enabled">Включен</label>
							<select id="profile_editor_enabled" name="enabled">
								<option value="1"<?=((int) ($arr['banned'] ?? 0) === 0 ? ' selected' : '');?>>Да</option>
								<option value="0"<?=((int) ($arr['banned'] ?? 0) !== 0 ? ' selected' : '');?>>Нет</option>
							</select>
						</div>
						<div class="settings-field">
							<label class="settings-field-label" for="profile_editor_uploaded_gb">Раздача, GB</label>
							<input id="profile_editor_uploaded_gb" type="number" name="uploaded_gb" value="<?=htmlspecialchars((string) $profileEditorUploadedGb, ENT_QUOTES, 'UTF-8');?>" min="0" step="0.001">
						</div>
						<div class="settings-field">
							<label class="settings-field-label" for="profile_editor_downloaded_gb">Скачано, GB</label>
							<input id="profile_editor_downloaded_gb" type="number" name="downloaded_gb" value="<?=htmlspecialchars((string) $profileEditorDownloadedGb, ENT_QUOTES, 'UTF-8');?>" min="0" step="0.001">
						</div>
						<div class="settings-field">
							<label class="settings-field-label" for="profile_editor_bonus">Бонусы</label>
							<input id="profile_editor_bonus" type="number" name="bonus_value" value="<?=htmlspecialchars((string) $profileEditorBonusValue, ENT_QUOTES, 'UTF-8');?>" min="0" step="0.001">
						</div>
						<div class="settings-field">
							<label class="settings-field-label" for="profile_editor_money">Монеты</label>
							<input id="profile_editor_money" type="number" name="money" value="<?=htmlspecialchars((string) (int) ($arr['money'] ?? 0), ENT_QUOTES, 'UTF-8');?>" min="0" step="1">
						</div>
						<div class="settings-field">
							<label class="settings-field-label" for="profile_editor_sex">Пол</label>
							<select id="profile_editor_sex" name="sex">
								<option value="1"<?=((int) ($arr['sex'] ?? 1) === 1 ? ' selected' : '');?>>Мужской</option>
								<option value="0"<?=((int) ($arr['sex'] ?? 1) === 0 ? ' selected' : '');?>>Женский</option>
							</select>
						</div>
						<div class="settings-field profile-editor-checks">
							<label class="settings-checkbox"><input type="checkbox" name="notify_comments" value="1"<?=(!empty($arr['notify_comments']) ? ' checked' : '');?>> Уведомлять о комментариях</label>
							<label class="settings-checkbox"><input type="checkbox" name="download_local_retracker" value="1"<?=(!isset($arr['download_local_retracker']) || !empty($arr['download_local_retracker']) ? ' checked' : '');?>> Локальный retracker</label>
							<label class="settings-checkbox"><input type="checkbox" name="theme_dark" value="1"<?=(!empty($arr['theme_dark']) ? ' checked' : '');?>> Темная тема</label>
							<label class="settings-checkbox"><input type="checkbox" name="bad_rating" value="1"<?=(!empty($arr['bad_rating']) ? ' checked' : '');?>> Плохой рейтинг</label>
							<label class="settings-checkbox"><input type="checkbox" name="confirm" value="1"<?=(!empty($arr['confirm']) ? ' checked' : '');?>> Аккаунт подтвержден</label>
							<label class="settings-checkbox"><input type="checkbox" name="reset_birthday" value="1"> Сбросить день рождения</label>
							<label class="settings-checkbox"><input type="checkbox" name="reset_passkey" value="1"> Сбросить passkey</label>
						</div>
						<div class="settings-field settings-field-full">
							<label class="settings-field-label" for="profile_editor_note">Заметка / комментарий в ЛС</label>
							<textarea class="settings-textarea" id="profile_editor_note" name="note"></textarea>
						</div>
					</div>

					<div class="profile-editor-actions">
						<button class="profile-card-button" type="submit">Сохранить</button>
					</div>
					<input type="hidden" name="user_id" value="<?=(int) $id;?>">
				</form>

				<?php if (!empty($profileEditorHistory)) { ?>
				<div class="profile-editor-history" data-profile-editor-history="1">
					<?php foreach ($profileEditorHistory as $historyItem) { ?>
					<div class="settings-history-item">
						<div class="settings-history-meta"><?=convent_date((string) ($historyItem['created_at'] ?? ''));?> · admin #<?=(int) ($historyItem['admin_id'] ?? 0);?></div>
						<div class="settings-history-text"><?=nl2br(htmlspecialchars((string) ($historyItem['note'] ?? ''), ENT_QUOTES, 'UTF-8'));?></div>
					</div>
					<?php } ?>
				</div>
				<?php } ?>
			</section>
			<?php } ?>

			<section class="profile-wall">
				<div class="profile-wall-header">
					<h2 class="profile-wall-title">Стена пользователя</h2>
				</div>

				<div class="profile-wall-body comment-thread-root" data-comment-thread="1" data-comment-type="users" data-object-id="<?=$id;?>" data-file="profile.php?id=<?=$id;?>&amp;" data-endpoint="ajax/comments.php" data-comments-sort="<?=htmlspecialchars(comments_current_sort(), ENT_QUOTES, 'UTF-8');?>">
					<div class="comment-ajax-notice" data-comment-notice="1" hidden></div>
					<div id="profile-wall-comments"><?=$wallCommentsHtml;?></div>

					<?php if (!empty($USER) && is_array($USER)) { ?>
					<form class="wall-form" id="profile-wall-form" data-comment-form="1" method="post" action="comments.take.php">
						<div class="wall-reply-banner" id="profile-wall-reply-info" data-comment-reply-banner="1" hidden>
							<span id="profile-wall-reply-label" data-comment-reply-label="1"></span>
							<button class="wall-comment-button" id="profile-wall-reply-cancel" data-comment-reply-cancel="1" type="button">Отмена</button>
						</div>

						<div class="wall-form-row">
							<div class="wall-form-avatar">
								<img src="<?=$currentUserWallAvatar;?>" alt="<?=htmlspecialchars((string) $USER['name'], ENT_QUOTES, 'UTF-8');?>" width="28" height="28">
							</div>
							<div class="wall-form-body">
								<textarea class="wall-form-textarea" id="profile-wall-text" data-comment-textarea="1" name="text"></textarea>
								<div class="wall-form-controls">
									<input class="wall-form-submit" value="Отправить" type="submit">
								</div>
							</div>
						</div>

						<input type="hidden" name="object_id" value="<?=$id;?>">
						<input type="hidden" name="type" value="users">
						<input type="hidden" name="file" value="profile.php?id=<?=$id;?>&amp;">
						<input type="hidden" name="parent_id" id="profile-wall-parent-id" data-comment-parent="1" value="0">
						<input type="hidden" name="act" value="add">
						<?=lt_csrf_input('comments_users_'.$id);?>
					</form>
					<?php } ?>
				</div>
			</section>
			<?php } elseif ($profileView === 'torrents') { ?>
			<section class="profile-section">
				<div class="profile-torrent-tabs">
					<?php foreach ($torrentTabLabels as $tabKey => $tabLabel) { ?>
					<a class="profile-torrent-tab<?=($torrentTab === $tabKey ? ' profile-torrent-tab-active' : '');?>" href="<?=profile_href($id, 'torrents', array('torrent_tab' => $tabKey));?>"><?=$tabLabel;?></a>
					<?php } ?>
				</div>

				<div class="profile-panel profile-torrent-panel">
					<?php if ($profileTorrentRows) { ?>
					<div class="profile-torrent-table-wrap">
						<table class="profile-torrent-table">
							<thead>
								<tr>
									<th>Торрент</th>
									<th>Категория</th>
									<th>Размер</th>
									<th>Дата</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($profileTorrentRows as $torrentRow) { ?>
								<tr>
									<td><a href="details.php?id=<?=(int) $torrentRow['id'];?>"><?=htmlspecialchars((string) $torrentRow['name'], ENT_QUOTES, 'UTF-8');?></a></td>
									<td><?=htmlspecialchars((string) ($torrentRow['category_name'] ?? 'Без категории'), ENT_QUOTES, 'UTF-8');?></td>
									<td><?=mksize((float) ($torrentRow['size'] ?? 0));?></td>
									<td><?=(!empty($torrentRow['activity_date']) ? convent_date($torrentRow['activity_date']) : '&mdash;');?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<?php } else { ?>
					<div class="profile-empty-state">Торрентов не найдено</div>
					<?php } ?>
				</div>
			</section>
			<?php } elseif ($profileView === 'bonus') { ?>
			<section class="profile-panel profile-bonus-panel">
				<form class="profile-bonus-form" method="post" action="<?=profile_href($id, 'bonus');?>">
					<?php foreach ($bonusOptions as $bonusOption) { ?>
					<label class="profile-bonus-option">
						<input class="profile-bonus-radio" type="radio" name="bonus_option" value="<?=$bonusOption['id'];?>"<?=($selectedBonusOption === $bonusOption['id'] ? ' checked' : '');?>>
						<span class="profile-bonus-copy">
							<span class="profile-bonus-title"><?=$bonusOption['label'];?></span>
							<span class="profile-bonus-text"><?=$bonusOption['description'];?></span>
							<?php if ($bonusOption['cost'] !== null) { ?>
							<span class="profile-bonus-cost">Стоимость: <?=$bonusOption['cost'];?> бонусов</span>
							<?php } ?>
						</span>
					</label>
					<?php } ?>

					<div class="profile-bonus-footer">
						<button class="profile-card-button" type="submit">Обменять</button>
						<div class="profile-bonus-available">Доступно для обмена: <strong><?=template_format_number($profileStats['bonus']);?> бонусов</strong></div>
					</div>

					<input type="hidden" name="act" value="exchange_bonus">
				</form>
			</section>
			<?php } ?>
		</div>

		<aside class="profile-sidebar">
			<?php if ($isOwnProfile) { ?>
			<section class="profile-sidebar-card profile-sidebar-nav-card">
				<div class="profile-sidebar-nav">
					<?php foreach ($sidebarLinks as $item) { ?>
					<div class="profile-sidebar-nav-item">
						<a class="profile-sidebar-link<?=(!empty($item['active']) ? ' profile-sidebar-link-active' : '');?>" href="<?=$item['href'];?>"><?=$item['label'];?></a>
					</div>
					<?php } ?>
				</div>
			</section>

			<section class="profile-sidebar-card">
				<h2 class="profile-sidebar-stats-title">Статистика</h2>
				<div class="profile-sidebar-stat-bonus">Бонус: <strong data-profile-display-bonus="1"><?=template_format_number($profileStats['bonus']);?></strong></div>

				<div class="profile-sidebar-stat-peers">
					<span class="profile-sidebar-stat-peer"><img src="public/images/up.png" alt="" width="10" height="10"> <?=$profileStats['seeders'];?></span>
					<span class="profile-sidebar-stat-divider">|</span>
					<span class="profile-sidebar-stat-peer"><img src="public/images/down.png" alt="" width="10" height="10"> <?=$profileStats['leechers'];?></span>
				</div>

				<div class="profile-sidebar-stat-transfer">
					<div class="profile-sidebar-stat-transfer-item profile-sidebar-stat-transfer-down" data-profile-display-downloaded="1"><?=$profileStats['downloaded'];?></div>
					<div class="profile-sidebar-stat-transfer-item profile-sidebar-stat-transfer-up" data-profile-display-uploaded="1"><?=$profileStats['uploaded'];?></div>
				</div>
			</section>
			<?php } else { ?>
			<section class="profile-sidebar-card profile-about-card">
				<h2 class="profile-about-title">Обо мне</h2>
				<div class="profile-about-text"><?=($profileAbout !== '' ? $profileAbout : 'Информация не заполнена.');?></div>
			</section>
			<?php } ?>
		</aside>
	</div>
</div>

<?php if ($profileCanMessage) { ?>
<div class="profile-modal-backdrop" id="profile-message-modal" hidden>
	<div class="profile-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="profile-message-title">
		<div class="profile-modal-header">
			<h2 class="profile-modal-title" id="profile-message-title">Новое сообщение</h2>
			<button class="profile-modal-close" type="button" data-profile-close-message="1">Закрыть</button>
		</div>

		<form class="profile-modal-form" id="profile-message-form" method="post" action="ajax/profile.php">
			<textarea class="profile-modal-textarea" id="profile-message-text" name="text"></textarea>
			<div class="profile-modal-actions">
				<button class="profile-card-button" type="submit">Отправить</button>

			</div>
			<input type="hidden" name="user_id" value="<?=$id;?>">
			<input type="hidden" name="name" value="Сообщение">
		</form>
	</div>
</div>
<?php } ?>

<script type="text/javascript" src="public/js/profile.js?v=<?=@filemtime('public/js/profile.js');?>"></script>
