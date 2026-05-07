<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

$settingsActiveTabRaw = (string) ($_GET['tab'] ?? '');
$settingsActiveTab = 'profile';
if ($settingsActiveTabRaw === 'password') {
	$settingsActiveTab = 'password';
} elseif ($settingsActiveTabRaw === 'moderation' && !empty($canModerateProfile)) {
	$settingsActiveTab = 'moderation';
}
?>
<div class="settings-page">
	<div class="settings-layout">
		<nav class="settings-nav" aria-label="Навигация по настройкам">
			<div class="settings-nav-card">
				<a class="settings-nav-link" href="my.setting.php?id=<?=$id;?>&amp;tab=profile" data-settings-tab="profile">Общие</a>
				<a class="settings-nav-link" href="my.setting.php?id=<?=$id;?>&amp;tab=password" data-settings-tab="password">Сменить пароль</a>
				<?php if (!empty($canModerateProfile)) { ?>
				<a class="settings-nav-link" href="my.setting.php?id=<?=$id;?>&amp;tab=moderation" data-settings-tab="moderation">Модерирование</a>
				<?php } ?>
			</div>
		</nav>

		<div class="settings-content">
			<div class="settings-tab-pane" id="settings-tab-profile">
				<h1 class="settings-title">Настройки профиля</h1>

				<form class="settings-form" action="my.setting.take.php?id=<?=$id;?>" method="post" enctype="multipart/form-data">
					<?=lt_csrf_input('settings_profile_'.$id);?>
					<section class="settings-section">
						<div class="settings-profile-top">
							<div class="settings-field">
								<label class="settings-field-label">Аватар</label>
								<div class="settings-avatar-row">
									<div class="settings-avatar-preview">
										<img src="<?=$avatarPath;?>" alt="<?=htmlspecialchars($arr['name'], ENT_QUOTES, 'UTF-8');?>" width="82" height="82">
									</div>
									<div class="settings-avatar-actions">
										<label class="settings-upload-button" for="avatar_upload">Загрузить аватар</label>
										<input class="settings-upload-input" id="avatar_upload" type="file" name="avatar_upload" accept=".jpg,.jpeg,.png,.gif">
										<?php if (!empty($arr['avatar'])) { ?>
										<a class="settings-remove-button" href="my.setting.take.php?id=<?=$id;?>&amp;act=foto_delete&amp;<?=lt_csrf_query('settings_avatar_'.$id);?>">Удалить</a>
										<?php } ?>
									</div>
								</div>
							</div>

							<div class="settings-field">
								<label class="settings-field-label">Дата рождения</label>
								<div class="settings-birthday">
									<select name="birthday_day">
										<option value="">День</option>
										<?php for ($day = 1; $day <= 31; $day++) { ?>
										<?php $value = sprintf('%02d', $day); ?>
										<option value="<?=$value;?>"<?=($birthdayDay === $value ? ' selected' : '');?>><?=$day;?></option>
										<?php } ?>
									</select>

									<select name="birthday_month">
										<option value="">Месяц</option>
										<?php foreach ($birthdayMonths as $monthValue => $monthLabel) { ?>
										<option value="<?=$monthValue;?>"<?=($birthdayMonth === $monthValue ? ' selected' : '');?>><?=$monthLabel;?></option>
										<?php } ?>
									</select>

									<select name="birthday_year">
										<option value="">Год</option>
										<?php for ($year = (int) date('Y'); $year >= 1920; $year--) { ?>
										<option value="<?=$year;?>"<?=((string) $birthdayYear === (string) $year ? ' selected' : '');?>><?=$year;?></option>
										<?php } ?>
									</select>
								</div>

								<div class="settings-field settings-field-nested">
									<label class="settings-field-label">Пол</label>
									<div class="settings-radio-group">
										<label class="settings-radio"><input type="radio" name="sex" value="1"<?=($arr['sex'] ? ' checked' : '');?>> <?=$language['setting_34'];?></label>
										<label class="settings-radio"><input type="radio" name="sex" value="0"<?=(!$arr['sex'] ? ' checked' : '');?>> <?=$language['setting_35'];?></label>
									</div>
								</div>
							</div>
						</div>

						<div class="settings-field settings-field-full">
							<label class="settings-field-label" for="settings_profile_text">О себе</label>
							<textarea class="settings-textarea" id="settings_profile_text" name="profile_text"><?=htmlspecialchars($arr['profile_text'], ENT_QUOTES, 'UTF-8');?></textarea>
						</div>
					</section>

					<section class="settings-section">
						<h2 class="settings-section-title">Настройки оповещений</h2>
						<div class="settings-checkbox-list">
							<label class="settings-checkbox"><input type="checkbox" name="notify_comments" value="1"<?=(!empty($arr['notify_comments']) ? ' checked' : '');?>> О новых комментариях</label>
						</div>
					</section>

					<section class="settings-section">
						<h2 class="settings-section-title">Настройки торрентов</h2>
						<div class="settings-checkbox-list">
							<label class="settings-checkbox"><input type="checkbox" name="download_local_retracker" value="1"<?=(!isset($arr['download_local_retracker']) || !empty($arr['download_local_retracker']) ? ' checked' : '');?>> Включить retracker.local для скачиваемых торрентов</label>
						</div>
					</section>

					<section class="settings-section">
						<h2 class="settings-section-title">Экспериментальные настройки</h2>
						<div class="settings-checkbox-list">
							<label class="settings-checkbox"><input type="checkbox" name="theme_dark" value="1"<?=(!empty($arr['theme_dark']) ? ' checked' : '');?>> Включить тёмную тему</label>
						</div>
					</section>

					<?php if (!empty($targetHasPlus) || !empty($canManagePlus)) { ?>
					<section class="settings-section settings-plus-section">
						<h2 class="settings-section-title">Подписка Plus</h2>
						<div class="settings-inline-note">Статус: <strong><?=htmlspecialchars($plusStatusLabel, ENT_QUOTES, 'UTF-8');?></strong></div>

						<?php if (!empty($targetHasPlus)) { ?>
						<div class="settings-grid">
							<div class="settings-field">
								<label class="settings-field-label" for="settings_plus_badge">Бейдж у имени</label>
								<select id="settings_plus_badge" name="plus_badge">
									<?php foreach ($plusBadgeOptions as $badgeKey => $badgeMeta) { ?>
									<option value="<?=htmlspecialchars($badgeKey, ENT_QUOTES, 'UTF-8');?>"<?=((string) ($arr['plus_badge'] ?? 'star') === (string) $badgeKey ? ' selected' : '');?>><?=$badgeMeta['html'];?> <?=htmlspecialchars($badgeMeta['label'], ENT_QUOTES, 'UTF-8');?></option>
									<?php } ?>
								</select>
								<div class="settings-inline-note">Пример около имени: Nick<?=lt_plus_badge_html($arr);?></div>
							</div>

							<div class="settings-field">
								<label class="settings-field-label" for="settings_profile_slug">Красивый никнейм</label>
								<input id="settings_profile_slug" type="text" name="profile_slug" value="<?=htmlspecialchars((string) ($arr['profile_slug'] ?? ''), ENT_QUOTES, 'UTF-8');?>" maxlength="64" placeholder="nickname">
								<div class="settings-inline-note">Ссылка профиля будет без ID: <?=(!empty($arr['profile_slug']) ? htmlspecialchars('/'.rawurlencode((string) $arr['profile_slug']), ENT_QUOTES, 'UTF-8') : '/nickname');?></div>
							</div>
						</div>
						<?php } ?>

						<?php if (!empty($canManagePlus)) { ?>
						<div class="settings-field settings-field-full">
							<label class="settings-field-label">Ручная выдача Plus</label>
							<div class="settings-radio-group">
								<label class="settings-radio"><input type="radio" name="plus_grant_mode" value="keep" checked> Не менять</label>
								<label class="settings-radio"><input type="radio" name="plus_grant_mode" value="until"> До даты</label>
								<label class="settings-radio"><input type="radio" name="plus_grant_mode" value="forever"> Навсегда</label>
								<label class="settings-radio"><input type="radio" name="plus_grant_mode" value="disable"> Отключить ручной Plus</label>
							</div>
							<input type="date" name="plus_manual_until" value="<?=(!empty($arr['plus_until']) && $arr['plus_until'] !== '0000-00-00 00:00:00' ? htmlspecialchars(substr((string) $arr['plus_until'], 0, 10), ENT_QUOTES, 'UTF-8') : '');?>">
						</div>
						<?php } ?>
					</section>
					<?php } ?>

					<?php if (!empty($canManageProfileClass) && !empty($profileClassOptions)) { ?>
					<section class="settings-section">
						<h2 class="settings-section-title">Администрирование</h2>
						<div class="settings-field">
							<label class="settings-field-label" for="settings_user_class">Класс пользователя</label>
							<select id="settings_user_class" name="class">
								<?php foreach ($profileClassOptions as $classRow) { ?>
								<option value="<?=(int) $classRow['id'];?>"<?=((int) $arr['class'] === (int) $classRow['id'] ? ' selected' : '');?>><?=htmlspecialchars($classRow['NAME'], ENT_QUOTES, 'UTF-8');?></option>
								<?php } ?>
							</select>
						</div>
					</section>
					<?php } ?>

					<div class="settings-actions">
						<button class="settings-submit" type="submit">Сохранить</button>
					</div>
				</form>
			</div>

			<div class="settings-tab-pane" id="settings-tab-password">
				<h1 class="settings-title">Сменить пароль</h1>

				<form class="settings-form" action="my.setting.take.php?act=password&id=<?=$id;?>" method="post">
					<?=lt_csrf_input('settings_password_'.$id);?>
					<section class="settings-section">
						<div class="settings-password-grid">
							<?php if ((int) $USER['id'] === (int) $id) { ?>
								<div class="settings-field">
									<label class="settings-field-label" for="old_password">Текущий пароль</label>
									<input id="old_password" type="password" name="old_password" value="">
								</div>
							<?php } ?>

							<div class="settings-field">
								<label class="settings-field-label" for="new_password">Новый пароль</label>
								<input id="new_password" type="password" name="new_password" value="">
							</div>
						</div>
					</section>

					<div class="settings-actions">
						<button class="settings-submit" type="submit">Изменить</button>
					</div>
				</form>
			</div>

			<?php if (!empty($canModerateProfile)) { ?>
			<div class="settings-tab-pane" id="settings-tab-moderation">
				<h1 class="settings-title">Модерирование профиля</h1>
				<div class="profile-inline-message" id="moderation-ajax-message" hidden></div>

				<form class="settings-form" id="settings-moderation-form">
					<section class="settings-section">
						<div class="settings-grid">
							<div class="settings-field">
								<label class="settings-field-label" for="moderation_name">Ник</label>
								<input id="moderation_name" type="text" name="name" value="<?=htmlspecialchars((string) ($arr['name'] ?? ''), ENT_QUOTES, 'UTF-8');?>" maxlength="12">
							</div>
							<div class="settings-field">
								<label class="settings-field-label" for="moderation_class">Класс</label>
								<select id="moderation_class" name="class">
									<?php foreach (get_classes_list() as $classRow) { ?>
									<option value="<?=(int) $classRow['id'];?>"<?=((int) ($arr['class'] ?? 0) === (int) $classRow['id'] ? ' selected' : '');?>><?=htmlspecialchars((string) $classRow['NAME'], ENT_QUOTES, 'UTF-8');?></option>
									<?php } ?>
								</select>
							</div>
							<div class="settings-field">
								<label class="settings-field-label" for="moderation_enabled">Включен</label>
								<select id="moderation_enabled" name="enabled">
									<option value="1"<?=((int) ($arr['banned'] ?? 0) === 0 ? ' selected' : '');?>>Да</option>
									<option value="0"<?=((int) ($arr['banned'] ?? 0) !== 0 ? ' selected' : '');?>>Нет</option>
								</select>
							</div>
							<div class="settings-field">
								<label class="settings-field-label" for="moderation_support_enabled">Поддержка</label>
								<select id="moderation_support_enabled" name="support_enabled">
									<option value="0"<?=((int) ($arr['support_enabled'] ?? 0) === 0 ? ' selected' : '');?>>Нет</option>
									<option value="1"<?=((int) ($arr['support_enabled'] ?? 0) === 1 ? ' selected' : '');?>>Да</option>
								</select>
							</div>
							<div class="settings-field">
								<label class="settings-field-label" for="moderation_support_until">Поддержка для</label>
								<input id="moderation_support_until" type="date" name="support_until" value="<?=(!empty($arr['support_until']) && $arr['support_until'] !== '0000-00-00 00:00:00' ? htmlspecialchars(substr((string) $arr['support_until'], 0, 10), ENT_QUOTES, 'UTF-8') : '');?>">
							</div>
							<div class="settings-field">
								<label class="settings-field-label" for="moderation_warning_until">Предупредить до</label>
								<input id="moderation_warning_until" type="date" name="warning_until" value="<?=(!empty($arr['warning_until']) && $arr['warning_until'] !== '0000-00-00 00:00:00' ? htmlspecialchars(substr((string) $arr['warning_until'], 0, 10), ENT_QUOTES, 'UTF-8') : '');?>">
							</div>
						</div>
					</section>

					<section class="settings-section">
						<div class="settings-grid">
							<div class="settings-field">
								<label class="settings-checkbox"><input type="checkbox" name="reset_birthday" value="1"> Сбросить день рождения</label>
								<label class="settings-checkbox"><input type="checkbox" name="reset_rating" value="1"> Убрать рейтинг</label>
								<label class="settings-checkbox"><input type="checkbox" name="reset_passkey" value="1"> Сбросить passkey</label>
							</div>
							<div class="settings-field">
								<label class="settings-field-label" for="moderation_uploaded_mb">Изменить раздачу (MB)</label>
								<input id="moderation_uploaded_mb" type="number" name="uploaded_mb" value="0" min="-1000000" max="1000000">
								<label class="settings-field-label" for="moderation_downloaded_mb">Изменить скачку (MB)</label>
								<input id="moderation_downloaded_mb" type="number" name="downloaded_mb" value="0" min="-1000000" max="1000000">
							</div>
							<div class="settings-field">
								<label class="settings-field-label" for="moderation_chat_ban">Чат бан</label>
								<select id="moderation_chat_ban" name="chat_ban">
									<option value="0"<?=((int) ($arr['chat_ban'] ?? 0) === 0 ? ' selected' : '');?>>Нет</option>
									<option value="1"<?=((int) ($arr['chat_ban'] ?? 0) === 1 ? ' selected' : '');?>>Да</option>
								</select>
								<label class="settings-field-label" for="moderation_in_group">В группе</label>
								<select id="moderation_in_group" name="in_group">
									<option value="0"<?=((int) ($arr['in_group'] ?? 0) === 0 ? ' selected' : '');?>>Нет</option>
									<option value="1"<?=((int) ($arr['in_group'] ?? 0) === 1 ? ' selected' : '');?>>Да</option>
								</select>
							</div>
						</div>
					</section>

					<section class="settings-section">
						<div class="settings-field settings-field-full">
							<label class="settings-field-label" for="moderation_note">Добавить заметку / Комментарий в ЛС</label>
							<textarea class="settings-textarea" id="moderation_note" name="note" placeholder="Комментарий для истории и уведомления пользователю"></textarea>
						</div>
						<div class="settings-field settings-field-full">
							<label class="settings-checkbox"><input type="checkbox" name="delete_user" value="1"> Удалить пользователя (безвозвратно)</label>
						</div>
					</section>

					<div class="settings-actions">
						<button class="settings-submit" type="submit">Сохранить через AJAX</button>
					</div>
					<input type="hidden" name="user_id" value="<?=(int) $id;?>">
				</form>

				<section class="settings-section">
					<h2 class="settings-section-title">История пользователя</h2>
					<?php if (!empty($moderationHistory)) { ?>
					<div class="settings-history-list">
						<?php foreach ($moderationHistory as $historyItem) { ?>
						<div class="settings-history-item">
							<div class="settings-history-meta"><?=convent_date((string) ($historyItem['created_at'] ?? ''));?> · admin #<?=(int) ($historyItem['admin_id'] ?? 0);?></div>
							<div class="settings-history-text"><?=nl2br(htmlspecialchars((string) ($historyItem['note'] ?? ''), ENT_QUOTES, 'UTF-8'));?></div>
						</div>
						<?php } ?>
					</div>
					<?php } else { ?>
					<div class="profile-empty-state">Записей пока нет.</div>
					<?php } ?>
				</section>
			</div>
			<?php } ?>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var navLinks = document.querySelectorAll('.settings-nav-link[data-settings-tab]');
	var panes = {
		profile: document.getElementById('settings-tab-profile'),
		password: document.getElementById('settings-tab-password'),
		moderation: document.getElementById('settings-tab-moderation')
	};

	function openSettingsTab(tab) {
		for (var key in panes) {
			if (!Object.prototype.hasOwnProperty.call(panes, key) || !panes[key]) {
				continue;
			}

			if (key === tab) {
				panes[key].style.display = '';
				panes[key].classList.add('settings-tab-pane-active');
			} else {
				panes[key].style.display = 'none';
				panes[key].classList.remove('settings-tab-pane-active');
			}
		}

		for (var i = 0; i < navLinks.length; i++) {
			if (navLinks[i].getAttribute('data-settings-tab') === tab) {
				navLinks[i].classList.add('settings-nav-link-active');
			} else {
				navLinks[i].classList.remove('settings-nav-link-active');
			}
		}
	}

	for (var i = 0; i < navLinks.length; i++) {
		navLinks[i].addEventListener('click', function (e) {
			e.preventDefault();
			var tab = this.getAttribute('data-settings-tab');
			openSettingsTab(tab);

			if (window.history && window.history.replaceState) {
				var url = new URL(window.location.href);
				url.searchParams.set('tab', tab);
				window.history.replaceState(null, '', url.toString());
			}
		});
	}

	openSettingsTab('<?=$settingsActiveTab;?>');

	var moderationForm = document.getElementById('settings-moderation-form');
	var moderationMessage = document.getElementById('moderation-ajax-message');
	if (moderationForm) {
		moderationForm.addEventListener('submit', function (event) {
			event.preventDefault();
			var formData = new FormData(moderationForm);
			formData.append('action', 'moderate_profile');
			fetch('ajax/profile.php', {
				method: 'POST',
				body: formData,
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json'
				}
			})
				.then(function (response) { return response.json(); })
				.then(function (payload) {
					if (!moderationMessage) {
						return;
					}
					moderationMessage.hidden = false;
					moderationMessage.textContent = payload.message || (payload.ok ? 'Сохранено.' : 'Ошибка');
					moderationMessage.className = 'profile-inline-message profile-inline-message-' + (payload.ok ? 'success' : 'error');
					if (payload.ok && payload.reload) {
						window.location.reload();
					}
				})
				.catch(function () {
					if (!moderationMessage) {
						return;
					}
					moderationMessage.hidden = false;
					moderationMessage.textContent = 'Не удалось сохранить изменения.';
					moderationMessage.className = 'profile-inline-message profile-inline-message-error';
				});
		});
	}
});
</script>
