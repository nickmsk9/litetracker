<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

$torrentCard = (is_array($torrentCard ?? null) ? $torrentCard : array());
$extraSections = (array) ($torrentCard['extra_sections'] ?? array());
$torrentCardName = htmlspecialchars((string) ($torrentCard['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$torrentCardCoverPath = trim((string) ($torrentCard['cover'] ?? ''));
$torrentCardHasValidCover = ($torrentCardCoverPath !== '' && stripos($torrentCardCoverPath, 'default_avatar.gif') === false);
$extraSummaryLabel = 'Дополнительная информация';
if (!empty($extraSections[0]['label'])) {
	$firstLabelKey = lt_torrent_label_key((string) ($extraSections[0]['label'] ?? ''));
	if ($firstLabelKey !== '' && $firstLabelKey !== 'дополнительно') {
		$extraSummaryLabel = (string) $extraSections[0]['label'];
	}
}
?>
<article class="browse-torrent-card lt-card<?=(!empty($torrentCard['is_banned']) ? ' is-banned' : '');?>">
	<div class="browse-torrent-card-full">
		<div class="browse-torrent-card-title-wrap">
			<h3 class="browse-torrent-card-title">
				<a href="<?=htmlspecialchars((string) ($torrentCard['details_href'] ?? ''), ENT_QUOTES, 'UTF-8');?>"><?=$torrentCardName;?></a>
			</h3>
		</div>

		<div class="browse-torrent-card-meta">
			<span class="browse-torrent-card-meta-item">
				<span class="browse-torrent-card-meta-icon" aria-hidden="true">
					<svg viewBox="0 0 16 16"><path d="M8 2 3.5 6.8h2.2V14h4.6V6.8H12.5L8 2Z" fill="currentColor"/></svg>
				</span>
				<span><?=htmlspecialchars((string) ($torrentCard['seeders'] ?? '0'), ENT_QUOTES, 'UTF-8');?></span>
			</span>
			<span class="browse-torrent-card-meta-item">
				<span class="browse-torrent-card-meta-icon" aria-hidden="true">
					<svg viewBox="0 0 16 16"><path d="M8 14 12.5 9.2h-2.2V2H5.7v7.2H3.5L8 14Z" fill="currentColor"/></svg>
				</span>
				<span><?=htmlspecialchars((string) ($torrentCard['leechers'] ?? '0'), ENT_QUOTES, 'UTF-8');?></span>
			</span>
			<span class="browse-torrent-card-meta-item">
				<span><?=htmlspecialchars((string) ($torrentCard['size'] ?? ''), ENT_QUOTES, 'UTF-8');?></span>
			</span>
			<span class="browse-torrent-card-meta-item">
				<a class="browse-torrent-card-user" href="<?=htmlspecialchars((string) ($torrentCard['user_href'] ?? 'profile.php'), ENT_QUOTES, 'UTF-8');?>">
					<span class="browse-torrent-card-meta-icon" aria-hidden="true">
						<svg viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm0 1.4c-2.7 0-5 1.4-5 3.1V14h10v-1.5c0-1.7-2.3-3.1-5-3.1Z" fill="currentColor"/></svg>
					</span>
					<span><?=$torrentCard['user_html'] ?? '';?></span>
				</a>
			</span>
			<span class="browse-torrent-card-meta-item">
				<span class="browse-torrent-card-meta-label">Обновлён:</span>
				<span><?=htmlspecialchars((string) ($torrentCard['updated_label'] ?? ''), ENT_QUOTES, 'UTF-8');?></span>
			</span>
		</div>

		<div class="browse-torrent-card-body">
			<div class="browse-torrent-card-poster-col">
				<a class="browse-torrent-card-cover" href="<?=htmlspecialchars((string) ($torrentCard['details_href'] ?? ''), ENT_QUOTES, 'UTF-8');?>">
					<label class="browse-torrent-card-cover-badge"><?=htmlspecialchars((string) ($torrentCard['category_badge'] ?? 'торрент'), ENT_QUOTES, 'UTF-8');?></label>
					<?php if (!empty($torrentCard['is_multitracker'])) { ?>
					<span class="browse-torrent-card-multi-badge">multi</span>
					<?php } ?>
					<?php if ($torrentCardHasValidCover) { ?>
					<img src="<?=htmlspecialchars($torrentCardCoverPath, ENT_QUOTES, 'UTF-8');?>" alt="<?=$torrentCardName;?>">
					<?php } else { ?>
					<span class="browse-torrent-card-cover-placeholder">Постер отсутствует</span>
					<?php } ?>
				</a>
			</div>

			<div class="browse-torrent-card-copy">
				<div class="browse-torrent-card-copy-body">
					<u class="browse-torrent-card-info-title"><?=htmlspecialchars((string) ($torrentCard['info_title'] ?? 'Информация о релизе'), ENT_QUOTES, 'UTF-8');?></u><br>
					<?php foreach ((array) ($torrentCard['main_items'] ?? array()) as $item) { ?>
					<strong><?=htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');?>: </strong><?=lt_torrent_render_text_html((string) ($item['value'] ?? ''));?><br>
					<?php } ?>
					<?php if (!empty($torrentCard['description_excerpt'])) { ?>
					<strong>Описание: </strong><?=htmlspecialchars((string) ($torrentCard['description_excerpt'] ?? ''), ENT_QUOTES, 'UTF-8');?><br>
					<?php } ?>

					<?php if (!empty($torrentCard['info_note'])) { ?>
					<br>
					<details class="browse-torrent-card-spoiler">
						<summary><?=htmlspecialchars($extraSummaryLabel, ENT_QUOTES, 'UTF-8');?></summary>
						<div class="browse-torrent-card-spoiler-content"><?=htmlspecialchars((string) ($torrentCard['info_note'] ?? ''), ENT_QUOTES, 'UTF-8');?></div>
					</details><br>
					<?php } ?>

					<?php foreach ($extraSections as $sectionIndex => $section) { ?>
					<?php $sectionLabel = trim((string) ($section['label'] ?? '')); ?>
					<?php if ($sectionLabel !== '') { ?>
					<u class="browse-torrent-card-extra-title"><?=htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8');?></u><br>
					<?php } ?>
					<?php foreach ((array) ($section['items'] ?? array()) as $item) { ?>
					<strong><?=htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');?>: </strong><?=lt_torrent_render_text_html((string) ($item['value'] ?? ''));?><br>
					<?php } ?>
					<?php if ($sectionIndex < count($extraSections) - 1) { ?>
					<br>
					<?php } ?>
					<?php } ?>

					<?php if (!empty($torrentCard['update_reason'])) { ?>
					<br>
					<strong class="browse-torrent-card-update-title">Торрент был обновлен</strong><br>
					Причина: <?=htmlspecialchars((string) ($torrentCard['update_reason'] ?? ''), ENT_QUOTES, 'UTF-8');?>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>

	<div class="browse-torrent-card-compact">
		<div class="browse-torrent-card-compact-inner">
			<a class="browse-torrent-card-compact-cover" href="<?=htmlspecialchars((string) ($torrentCard['details_href'] ?? ''), ENT_QUOTES, 'UTF-8');?>">
				<label class="browse-torrent-card-cover-badge browse-torrent-card-cover-badge-compact"><?=htmlspecialchars((string) ($torrentCard['category_badge'] ?? 'торрент'), ENT_QUOTES, 'UTF-8');?></label>
				<?php if (!empty($torrentCard['is_multitracker'])) { ?>
				<span class="browse-torrent-card-multi-badge browse-torrent-card-multi-badge-compact">m</span>
				<?php } ?>
				<?php if ($torrentCardHasValidCover) { ?>
				<img src="<?=htmlspecialchars($torrentCardCoverPath, ENT_QUOTES, 'UTF-8');?>" alt="<?=$torrentCardName;?>">
				<?php } else { ?>
				<span class="browse-torrent-card-cover-placeholder browse-torrent-card-cover-placeholder-compact">Нет</span>
				<?php } ?>
			</a>

			<div class="browse-torrent-card-compact-content">
				<h3 class="browse-torrent-card-compact-title">
					<a href="<?=htmlspecialchars((string) ($torrentCard['details_href'] ?? ''), ENT_QUOTES, 'UTF-8');?>"><?=$torrentCardName;?></a>
				</h3>
				<div class="browse-torrent-card-meta browse-torrent-card-meta-compact">
					<span class="browse-torrent-card-meta-item">
						<span class="browse-torrent-card-meta-icon" aria-hidden="true">
							<svg viewBox="0 0 16 16"><path d="M8 2 3.5 6.8h2.2V14h4.6V6.8H12.5L8 2Z" fill="currentColor"/></svg>
						</span>
						<span><?=htmlspecialchars((string) ($torrentCard['seeders'] ?? '0'), ENT_QUOTES, 'UTF-8');?></span>
					</span>
					<span class="browse-torrent-card-meta-item">
						<span class="browse-torrent-card-meta-icon" aria-hidden="true">
							<svg viewBox="0 0 16 16"><path d="M8 14 12.5 9.2h-2.2V2H5.7v7.2H3.5L8 14Z" fill="currentColor"/></svg>
						</span>
						<span><?=htmlspecialchars((string) ($torrentCard['leechers'] ?? '0'), ENT_QUOTES, 'UTF-8');?></span>
					</span>
					<span class="browse-torrent-card-meta-item">
						<span><?=htmlspecialchars((string) ($torrentCard['size'] ?? ''), ENT_QUOTES, 'UTF-8');?></span>
					</span>
					<span class="browse-torrent-card-meta-item">
						<a class="browse-torrent-card-user" href="<?=htmlspecialchars((string) ($torrentCard['user_href'] ?? 'profile.php'), ENT_QUOTES, 'UTF-8');?>">
							<span class="browse-torrent-card-meta-icon" aria-hidden="true">
								<svg viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm0 1.4c-2.7 0-5 1.4-5 3.1V14h10v-1.5c0-1.7-2.3-3.1-5-3.1Z" fill="currentColor"/></svg>
							</span>
							<span><?=$torrentCard['user_html'] ?? '';?></span>
						</a>
					</span>
					<span class="browse-torrent-card-meta-item">
						<span class="browse-torrent-card-meta-label">Обновлён:</span>
						<span><?=htmlspecialchars((string) ($torrentCard['updated_label'] ?? ''), ENT_QUOTES, 'UTF-8');?></span>
					</span>
				</div>
			</div>
		</div>
	</div>
</article>
