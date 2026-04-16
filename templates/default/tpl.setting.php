<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}
?>
<div class="settings-page">
	<div class="settings-layout">
		<nav class="settings-nav" aria-label="Навигация по настройкам">
			<?php foreach ($settingsMenu as $item) { ?>
			<a class="settings-nav-link<?=(!empty($item['active']) ? ' settings-nav-link-active' : '');?>" href="<?=$item['href'];?>"><?=$item['label'];?></a>
			<?php } ?>
		</nav>

		<div class="settings-content">
			<?php if ($settingsView === 'password') { ?>
			<h1 class="settings-title">Сменить пароль</h1>

			<form class="settings-form" action="my.setting.take.php?act=password&id=<?=$id;?>" method="post">
				<section class="settings-section">
					<div class="settings-grid">
						<?php if (!$PRIV['setting_user']) { ?>
						<div class="settings-field settings-field-full">
							<label class="settings-field-label" for="old_password">Текущий пароль</label>
							<input id="old_password" type="password" name="old_password" value="">
						</div>
						<?php } ?>

						<div class="settings-field settings-field-full">
							<label class="settings-field-label" for="new_password">Новый пароль</label>
							<input id="new_password" type="password" name="new_password" value="">
						</div>

						<div class="settings-field settings-field-full">
							<label class="settings-field-label" for="new_password_1">Повторите новый пароль</label>
							<input id="new_password_1" type="password" name="new_password_1" value="">
						</div>
					</div>
				</section>

				<div class="settings-actions">
					<button class="settings-submit" type="submit">Сохранить</button>
				</div>
			</form>
			<?php } else { ?>
			<h1 class="settings-title">Настройки профиля</h1>

			<form class="settings-form" action="my.setting.take.php?id=<?=$id;?>" method="post" enctype="multipart/form-data">
				<section class="settings-section">
					<div class="settings-grid">
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
							<div class="settings-inline-note">JPG, PNG или GIF. Изображение сохраняется в профиль и миниатюру.</div>
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

							<div class="settings-field" style="margin-top: 16px;">
								<label class="settings-field-label">Пол</label>
								<div class="settings-radio-group">
									<label class="settings-radio"><input type="radio" name="sex" value="1"<?=($arr['sex'] ? ' checked' : '');?>> <?=$language['setting_34'];?></label>
									<label class="settings-radio"><input type="radio" name="sex" value="0"<?=(!$arr['sex'] ? ' checked' : '');?>> <?=$language['setting_35'];?></label>
								</div>
							</div>
						</div>

						<div class="settings-field">
							<label class="settings-field-label" for="settings_name">Ник</label>
							<input id="settings_name" type="text" name="name" value="<?=htmlspecialchars($arr['name'], ENT_QUOTES, 'UTF-8');?>">
						</div>

						<div class="settings-field">
							<label class="settings-field-label" for="settings_email">E-mail</label>
							<input id="settings_email" type="email" name="email" value="<?=htmlspecialchars($arr['email'], ENT_QUOTES, 'UTF-8');?>">
						</div>

						<div class="settings-field settings-field-full">
							<label class="settings-field-label" for="settings_website">Сайт</label>
							<input id="settings_website" type="text" name="website" value="<?=htmlspecialchars($arr['website'], ENT_QUOTES, 'UTF-8');?>">
						</div>

						<div class="settings-field settings-field-full">
							<label class="settings-field-label" for="settings_profile_text">О себе</label>
							<textarea class="settings-textarea" id="settings_profile_text" name="profile_text"><?=htmlspecialchars($arr['profile_text'], ENT_QUOTES, 'UTF-8');?></textarea>
						</div>
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

				<section class="settings-section">
					<h2 class="settings-section-title">Доступ</h2>
					<div class="settings-security-card">
						<div class="settings-security-value">Passkey: <strong><?=htmlspecialchars($arr['passkey'], ENT_QUOTES, 'UTF-8');?></strong></div>
					</div>
					<div class="settings-secondary-actions">
						<a class="settings-action-link" href="my.setting.take.php?id=<?=$id;?>&act=passkey">Обновить пасскей</a>
					</div>
				</section>

				<?php if ($PRIV['setting_user']) { ?>
				<section class="settings-section">
					<h2 class="settings-section-title">Управление аккаунтом</h2>
					<div class="settings-admin-grid">
						<?php if ($USER['id'] != $arr['id'] && $PRIV['EDIT_PRIV']) { ?>
						<div class="settings-field">
							<label class="settings-field-label" for="settings_class">Класс</label>
							<select id="settings_class" name="class">
								<?php foreach ($classOptions as $class) { ?>
								<option value="<?=$class['id'];?>"<?=((int) $class['id'] === (int) $arr['class'] ? ' selected' : '');?>><?=htmlspecialchars($class['NAME'], ENT_QUOTES, 'UTF-8');?></option>
								<?php } ?>
							</select>
						</div>
						<?php } ?>

						<div class="settings-field">
							<label class="settings-field-label">Скачано</label>
							<div class="settings-admin-grid" style="grid-template-columns: 68px minmax(0, 1fr) 82px; gap: 8px;">
								<select name="down_command">
									<option value="+">+</option>
									<option value="-">-</option>
								</select>
								<input type="number" name="downloaded" value="" min="0">
								<select name="down_format">
									<option value="mb">MB</option>
									<option value="gb">GB</option>
								</select>
							</div>
							<div class="settings-inline-note">Сейчас: <?=mksize($arr['downloaded']);?></div>
						</div>

						<div class="settings-field">
							<label class="settings-field-label">Раздано</label>
							<div class="settings-admin-grid" style="grid-template-columns: 68px minmax(0, 1fr) 82px; gap: 8px;">
								<select name="up_command">
									<option value="+">+</option>
									<option value="-">-</option>
								</select>
								<input type="number" name="uploaded" value="" min="0">
								<select name="up_format">
									<option value="mb">MB</option>
									<option value="gb">GB</option>
								</select>
							</div>
							<div class="settings-inline-note">Сейчас: <?=mksize($arr['uploaded']);?></div>
						</div>
					</div>
				</section>
				<?php } ?>

				<?php if ($showModerationPanel) { ?>
				<section class="settings-section">
					<h2 class="settings-section-title">Администрирование</h2>
					<div class="settings-secondary-actions">
						<a class="settings-action-link" href="my.setting.take.php?id=<?=$id;?>&act=ban_account"><?=(!$arr['banned'] ? 'Забанить аккаунт' : 'Разбанить аккаунт');?></a>
						<a class="settings-action-link" href="my.setting.take.php?id=<?=$id;?>&act=ban_ip"><?=(!$isIpBanned ? 'Заблокировать IP' : 'Разблокировать IP');?></a>
					</div>
				</section>
				<?php } ?>

				<div class="settings-actions">
					<button class="settings-submit" type="submit">Сохранить</button>
				</div>
			</form>
			<?php } ?>
		</div>
	</div>
</div>
