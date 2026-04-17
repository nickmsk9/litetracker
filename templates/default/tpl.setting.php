<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}
?>
<div class="settings-page">
	<div class="settings-layout">
		<nav class="settings-nav" aria-label="Навигация по настройкам">
			<div class="settings-nav-card">
				<a class="settings-nav-link" href="#" data-settings-tab="profile">Общие</a>
				<a class="settings-nav-link" href="#" data-settings-tab="password">Сменить пароль</a>
			</div>
		</nav>

		<div class="settings-content">
			<div class="settings-tab-pane" id="settings-tab-profile">
				<h1 class="settings-title">Настройки профиля</h1>

				<form class="settings-form" action="my.setting.take.php?id=<?=$id;?>" method="post" enctype="multipart/form-data">
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
										<a class="settings-remove-button" href="my.setting.take.php?id=<?=$id;?>&act=foto_delete">Удалить</a>
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

					<div class="settings-actions">
						<button class="settings-submit" type="submit">Сохранить</button>
					</div>
				</form>
			</div>

			<div class="settings-tab-pane" id="settings-tab-password">
				<h1 class="settings-title">Сменить пароль</h1>

				<form class="settings-form" action="my.setting.take.php?act=password&id=<?=$id;?>" method="post">
					<section class="settings-section">
						<div class="settings-password-grid">
							<?php if (!$PRIV['setting_user']) { ?>
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
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var navLinks = document.querySelectorAll('.settings-nav-link[data-settings-tab]');
	var panes = {
		profile: document.getElementById('settings-tab-profile'),
		password: document.getElementById('settings-tab-password')
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
			openSettingsTab(this.getAttribute('data-settings-tab'));
		});
	}

	openSettingsTab('profile');
});
</script>
