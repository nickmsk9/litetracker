<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}
?>
<article class="newrel-card">
	<div class="newrel-card-full">
		<div class="newrel-card-full-head">
			<h2 class="newrel-card-full-title">
				<a href="details.php?id=<?=$torrentId;?>"><?=htmlspecialchars($name, ENT_QUOTES, 'UTF-8');?></a>
			</h2>
		</div>

		<div class="newrel-meta">
			<span class="newrel-meta-item">
				<img src="public/images/up.png" alt="">
				<span><?=$seeders;?></span>
			</span>

			<span class="newrel-meta-sep">|</span>

			<span class="newrel-meta-item">
				<img src="public/images/down.png" alt="">
				<span><?=$leechers;?></span>
			</span>

			<span class="newrel-meta-sep">|</span>

			<span class="newrel-meta-item">
				<span><?=$size;?></span>
			</span>

			<span class="newrel-meta-sep">|</span>

			<span class="newrel-meta-item">
				<img src="public/images/user.png" alt="">
				<span><?=get_user_color($userClass, htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'));?></span>
			</span>

			<?php if ($dateLabel !== '') { ?>
				<span class="newrel-meta-sep">|</span>
				<span class="newrel-meta-item">
					<span>Обновлён: <?=$dateLabel;?></span>
				</span>
			<?php } ?>
		</div>

		<div class="newrel-card-full-body">
			<a class="newrel-cover" href="details.php?id=<?=$torrentId;?>">
				<span class="newrel-cover-badge"><?=htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8');?></span>
				<img src="<?=htmlspecialchars($cover, ENT_QUOTES, 'UTF-8');?>" alt="<?=htmlspecialchars($name, ENT_QUOTES, 'UTF-8');?>">
			</a>

			<div class="newrel-content">
				<div class="newrel-section-title">Информация об аниме</div>

				<div class="newrel-facts">
					<?php if ($country !== '') { ?><div class="newrel-fact"><b>Страна:</b> <?=htmlspecialchars($country, ENT_QUOTES, 'UTF-8');?></div><?php } ?>
					<?php if ($quality !== '') { ?><div class="newrel-fact"><b>Тип:</b> <?=htmlspecialchars($quality, ENT_QUOTES, 'UTF-8');?></div><?php } ?>
					<?php if ($genre !== '') { ?><div class="newrel-fact"><b>Жанр:</b> <?=htmlspecialchars($genre, ENT_QUOTES, 'UTF-8');?></div><?php } ?>
					<?php if ($year !== '') { ?><div class="newrel-fact"><b>Год выхода:</b> <?=htmlspecialchars($year, ENT_QUOTES, 'UTF-8');?></div><?php } ?>
					<?php if ($series !== '') { ?><div class="newrel-fact"><b>Кол серий:</b> <?=htmlspecialchars($series, ENT_QUOTES, 'UTF-8');?></div><?php } ?>
					<?php if ($director !== '') { ?><div class="newrel-fact"><b>Режиссёр:</b> <?=htmlspecialchars($director, ENT_QUOTES, 'UTF-8');?></div><?php } ?>
					<?php if ($duration !== '') { ?><div class="newrel-fact"><b>Продолжительность:</b> <?=htmlspecialchars($duration, ENT_QUOTES, 'UTF-8');?></div><?php } ?>

					<?php if ($descr !== '') { ?>
						<div class="newrel-description">
							<b>Описание:</b> <?=htmlspecialchars($descr, ENT_QUOTES, 'UTF-8');?>
						</div>
					<?php } ?>
				</div>

				<div class="newrel-section-title">Дополнительно</div>

				<div class="newrel-facts">
					<?php if ($formatValue !== '') { ?><div class="newrel-fact"><b>Формат:</b> <?=htmlspecialchars($formatValue, ENT_QUOTES, 'UTF-8');?></div><?php } ?>
					<?php if ($resolution !== '') { ?><div class="newrel-fact"><b>Разрешение:</b> <?=htmlspecialchars($resolution, ENT_QUOTES, 'UTF-8');?></div><?php } ?>
					<?php if ($subtitles !== '') { ?><div class="newrel-fact"><b>Субтитры:</b> <?=htmlspecialchars($subtitles, ENT_QUOTES, 'UTF-8');?></div><?php } ?>
					<?php if ($languageValue !== '') { ?><div class="newrel-fact"><b>Язык:</b> <?=htmlspecialchars($languageValue, ENT_QUOTES, 'UTF-8');?></div><?php } ?>
				</div>

				<div class="newrel-update-title">Торрент был обновлен</div>
				<div>Причина: <?=htmlspecialchars($updateReason !== '' ? $updateReason : 'Добавлены новые данные по релизу.', ENT_QUOTES, 'UTF-8');?></div>
			</div>
		</div>
	</div>

	<div class="newrel-card-compact">
		<div class="newrel-card-compact-inner">
			<a class="newrel-compact-cover" href="details.php?id=<?=$torrentId;?>">
				<span class="newrel-compact-cover-badge"><?=htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8');?></span>
				<img src="<?=htmlspecialchars($cover, ENT_QUOTES, 'UTF-8');?>" alt="<?=htmlspecialchars($name, ENT_QUOTES, 'UTF-8');?>">
			</a>

			<div class="newrel-card-compact-content">
				<h3 class="newrel-card-compact-title">
					<a href="details.php?id=<?=$torrentId;?>"><?=htmlspecialchars($name, ENT_QUOTES, 'UTF-8');?></a>
				</h3>

				<div class="newrel-meta">
					<span class="newrel-meta-item">
						<img src="public/images/up.png" alt="">
						<span><?=$seeders;?></span>
					</span>

					<span class="newrel-meta-sep">|</span>

					<span class="newrel-meta-item">
						<img src="public/images/down.png" alt="">
						<span><?=$leechers;?></span>
					</span>

					<span class="newrel-meta-sep">|</span>

					<span class="newrel-meta-item">
						<span><?=$size;?></span>
					</span>

					<span class="newrel-meta-sep">|</span>

					<span class="newrel-meta-item">
						<img src="public/images/user.png" alt="">
						<span><?=get_user_color($userClass, htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'));?></span>
					</span>

					<?php if ($dateLabel !== '') { ?>
						<span class="newrel-meta-sep">|</span>
						<span class="newrel-meta-item">
							<span>Обновлён: <?=$dateLabel;?></span>
						</span>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</article>
