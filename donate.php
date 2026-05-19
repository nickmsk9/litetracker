<?php
/*
===================================================================
-------------------------------------------------------------------
Назначение: Страница поддержки проекта
===================================================================
*/

require __DIR__ . '/app/system/init.php';

head('Поддержка проекта');
begin_frame('Поддержка проекта');

$supportMethods = array();

if (!empty($config['wmz_number'])) {
	$supportMethods[] = array(
		'title' => 'WebMoney WMZ',
		'value' => (string) $config['wmz_number'],
	);
}

if (!empty($config['wmr_number'])) {
	$supportMethods[] = array(
		'title' => 'WebMoney WMR',
		'value' => (string) $config['wmr_number'],
	);
}

$supportText = trim((string) ($config['project_help_text'] ?? ''));
if ($supportText === '') {
	$supportText = 'Оплата аренды сервера, принимаем любую помощь.';
}

$supportEmail = trim((string) ($config['mail']['from'] ?? ''));
$supportMailHref = ($supportEmail !== '' ? 'mailto:'.$supportEmail.'?subject='.rawurlencode('Поддержка проекта') : '');
?>
<div class="donate-page">
	<p class="donate-lead"><?=htmlspecialchars($supportText, ENT_QUOTES, 'UTF-8');?></p>
	<?php if ($supportMethods) { ?>
	<div class="donate-methods">
		<?php foreach ($supportMethods as $method) { ?>
		<div class="donate-method">
			<div class="donate-method-title"><?=htmlspecialchars($method['title'], ENT_QUOTES, 'UTF-8');?></div>
			<div class="donate-method-value"><?=htmlspecialchars($method['value'], ENT_QUOTES, 'UTF-8');?></div>
		</div>
		<?php } ?>
	</div>
	<p class="donate-note">После перевода можно написать администрации, чтобы платеж быстрее отметили на сайте.</p>
	<?php } else { ?>
	<div class="donate-empty">
		Способ оплаты пока не настроен. Укажите кошелек в переменных окружения <b>LITETRACKER_WMZ_NUMBER</b> или <b>LITETRACKER_WMR_NUMBER</b>.
	</div>
	<?php } ?>
	<?php if (!$USER) { ?>
	<div class="donate-actions">
		<a class="donate-button donate-button-primary" href="login.php?referer=donate.php">Войти в аккаунт</a>
		<a class="donate-button" href="index.php">На главную</a>
	</div>
	<?php } else { ?>
	<div class="donate-actions">
		<?php if ($supportMailHref !== '') { ?>
		<a class="donate-button donate-button-primary" href="<?=htmlspecialchars($supportMailHref, ENT_QUOTES, 'UTF-8');?>">Написать администрации</a>
		<?php } ?>
		<a class="donate-button" href="index.php">На главную</a>
	</div>
	<?php } ?>
</div>
<?php
end_frame();
foot();
?>
