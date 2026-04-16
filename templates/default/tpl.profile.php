<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}
?>
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
					<?php listComment('users', $id, 'profile.php?', 0); ?>
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
