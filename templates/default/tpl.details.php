<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

$detailsInfoTitle = lt_details_info_heading($cat_name_plain);
$detailsRatingPercent = max(0, min(100, ($details_rating_score / 5) * 100));
$detailsDescriptionHtml = $details_description_html;
if ($detailsDescriptionHtml === '' && !$details_has_structured_content) {
	$detailsDescriptionHtml = $descr;
}
?>

<div class="details-page">
	<div class="details-layout">
		<aside class="details-sidebar">
			<div class="details-poster-card">
				<div class="details-poster-badge"><?=htmlspecialchars($category_badge !== '' ? $category_badge : 'торрент', ENT_QUOTES, 'UTF-8');?></div>
				<img class="details-poster-image" src="<?=htmlspecialchars($image, ENT_QUOTES, 'UTF-8');?>" alt="<?=$name;?>">
			</div>

			<div class="details-sidebar-actions">
				<?php if ($details_download_href !== '') { ?>
				<div class="details-download-group">
					<a class="details-download-button" href="<?=htmlspecialchars($details_download_href, ENT_QUOTES, 'UTF-8');?>"><?=$language['details_2'];?></a>
					<?php if ($details_magnet_href !== '') { ?>
					<a class="details-download-button details-download-button-magnet" href="<?=htmlspecialchars($details_magnet_href, ENT_QUOTES, 'UTF-8');?>" aria-label="<?=$language['details_3'];?>">m</a>
					<?php } ?>
				</div>
				<?php } elseif ($details_guest_login_href !== '' || $details_guest_register_href !== '') { ?>
				<div class="details-guest-box">
					<div class="details-guest-copy"><?=htmlspecialchars($details_guest_notice, ENT_QUOTES, 'UTF-8');?></div>
					<div class="details-guest-actions">
						<?php if ($details_guest_register_href !== '') { ?>
						<a class="details-download-button" href="<?=htmlspecialchars($details_guest_register_href, ENT_QUOTES, 'UTF-8');?>">Зарегистрироваться</a>
						<?php } ?>
						<?php if ($details_guest_login_href !== '') { ?>
						<a class="details-bookmark-button details-guest-login-button" href="<?=htmlspecialchars($details_guest_login_href, ENT_QUOTES, 'UTF-8');?>">Войти</a>
						<?php } ?>
					</div>
				</div>
				<?php } ?>

				<?php if ($details_bookmark_href !== '') { ?>
				<a
					class="details-bookmark-button<?=(!empty($details_bookmarked) ? ' details-bookmark-button-active' : '');?>"
					href="<?=htmlspecialchars($details_bookmark_href, ENT_QUOTES, 'UTF-8');?>"
					data-details-bookmark="1"
					data-bookmarked="<?=(!empty($details_bookmarked) ? '1' : '0');?>"
				><?=htmlspecialchars($details_bookmark_label, ENT_QUOTES, 'UTF-8');?></a>
				<?php } ?>

				<?php if ($details_edit_href !== '') { ?>
				<a class="details-edit-link" href="<?=htmlspecialchars($details_edit_href, ENT_QUOTES, 'UTF-8');?>">Редактировать релиз</a>
				<?php } ?>
			</div>
		</aside>

		<div class="details-main">
			<section class="details-panel details-title-panel">
				<h1 class="details-title"><?=$name;?></h1>
				<?php if ($details_status_badges) { ?>
				<div class="details-badges">
					<?php foreach ($details_status_badges as $badge) { ?>
					<span class="details-badge<?=(!empty($badge['class']) ? ' '.htmlspecialchars($badge['class'], ENT_QUOTES, 'UTF-8') : '');?>"><?=htmlspecialchars($badge['label'], ENT_QUOTES, 'UTF-8');?></span>
					<?php } ?>
				</div>
				<?php } ?>
			</section>

			<?php if (!$infohash) { ?>
			<div class="details-panel details-alert-panel">
				Этот релиз пока нельзя скачать: у него нет torrent-файла.
			</div>
			<?php } ?>

			<section class="details-panel details-meta-panel">
				<div class="details-rating-row">
					<div class="details-rating-block">
						<div class="details-rating-stars" aria-label="Рейтинг <?=htmlspecialchars(number_format($details_rating_score, 1), ENT_QUOTES, 'UTF-8');?>">
							<span class="details-rating-stars-base">★★★★★</span>
							<span class="details-rating-stars-fill" style="width: <?=$detailsRatingPercent;?>%;">★★★★★</span>
							<?php if ($details_rating_can_vote) { ?>
							<span class="details-rating-vote" aria-label="Оцените раздачу">
							<?php for ($ratingIndex = 1; $ratingIndex <= 5; $ratingIndex++) { ?>
							<a class="details-rating-vote-star" href="details.php?id=<?=(int) $id;?>&amp;rating=<?=$ratingIndex;?>" aria-label="Оценить на <?=$ratingIndex;?> из 5">★</a>
							<?php } ?>
							</span>
							<?php } ?>
						</div>
					</div>
					<div class="details-rating-meta">
						<div class="details-rating-count">(<?=number_format((int) $details_rating_votes);?> оценок)</div>
						<?php if ($details_rating_feedback !== '') { ?>
						<div class="details-rating-note"><?=htmlspecialchars($details_rating_feedback, ENT_QUOTES, 'UTF-8');?></div>
						<?php } ?>
					</div>
				</div>

				<div class="details-meta-stats">
					<span class="details-meta-item">
						<span class="details-meta-icon" aria-hidden="true">
							<svg viewBox="0 0 16 16"><path d="M8 2 3.5 6.8h2.2V14h4.6V6.8H12.5L8 2Z" fill="currentColor"/></svg>
						</span>
						<span><?=$seeders;?></span>
					</span>
					<span class="details-meta-item">
						<span class="details-meta-icon" aria-hidden="true">
							<svg viewBox="0 0 16 16"><path d="M8 14 12.5 9.2h-2.2V2H5.7v7.2H3.5L8 14Z" fill="currentColor"/></svg>
						</span>
						<span><?=$leechers;?></span>
					</span>
					<span class="details-meta-item">
						<span><?=$size;?></span>
					</span>
					<span class="details-meta-item">
						<span class="details-meta-icon" aria-hidden="true">
							<svg viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm0 1.4c-2.7 0-5 1.4-5 3.1V14h10v-1.5c0-1.7-2.3-3.1-5-3.1Z" fill="currentColor"/></svg>
						</span>
						<span><a class="details-user-link" href="<?=profile_href($id_user);?>"><?=htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8');?></a></span>
					</span>
					<span class="details-meta-item">
						<span class="details-meta-icon" aria-hidden="true">
							<svg viewBox="0 0 16 16"><path d="M8 3C4.3 3 1.2 5 0 8c1.2 3 4.3 5 8 5s6.8-2 8-5c-1.2-3-4.3-5-8-5Zm0 8.1A3.1 3.1 0 1 1 8 4.9a3.1 3.1 0 0 1 0 6.2Zm0-1.7A1.4 1.4 0 1 0 8 6.6a1.4 1.4 0 0 0 0 2.8Z" fill="currentColor"/></svg>
						</span>
						<span><?=number_format((int) $details_views_count);?></span>
					</span>
					<span class="details-meta-item">
						<span class="details-meta-icon" aria-hidden="true">
							<svg viewBox="0 0 16 16"><path d="M13.6 3.1 6.3 10.4 2.4 6.5l1.3-1.3 2.6 2.6 6-6Z" fill="currentColor"/></svg>
						</span>
						<span><?=$completed;?></span>
					</span>
				</div>

				<div class="details-date-row">
					<span><span class="details-date-label">Обновлён:</span> <?=$details_updated_label;?></span>
					<span class="details-date-separator">|</span>
					<span><span class="details-date-label">Создан:</span> <?=$details_created_label;?></span>
				</div>
			</section>

			<section class="details-panel details-info-panel">
				<div class="details-section-group">
					<h2 class="details-section-title"><?=$detailsInfoTitle;?></h2>
					<?php if ($details_main_items) { ?>
					<dl class="details-info-list">
						<?php foreach ($details_main_items as $item) { ?>
						<div class="details-info-row">
							<dt><?=htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');?>:</dt>
							<dd><?=lt_details_render_text_html((string) ($item['value'] ?? ''));?></dd>
						</div>
						<?php } ?>
					</dl>
					<?php } ?>

					<?php if ($detailsDescriptionHtml !== '') { ?>
					<div class="details-copy-block"><?=$detailsDescriptionHtml;?></div>
					<?php } ?>
				</div>

				<?php foreach ($details_extra_sections as $section) { ?>
				<details class="details-disclosure">
					<summary><?=htmlspecialchars((string) ($section['label'] ?? ''), ENT_QUOTES, 'UTF-8');?></summary>
					<div class="details-disclosure-body">
						<dl class="details-info-list">
							<?php foreach ((array) ($section['items'] ?? array()) as $item) { ?>
							<div class="details-info-row">
								<dt><?=htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');?>:</dt>
								<dd><?=lt_details_render_text_html((string) ($item['value'] ?? ''));?></dd>
							</div>
							<?php } ?>
						</dl>
					</div>
				</details>
				<?php } ?>

				<?php if ($details_has_update || $details_update_reason !== '') { ?>
				<div class="details-section-group">
					<h2 class="details-section-title">Торрент был обновлен</h2>
					<div class="details-update-copy">
						<?php if ($details_update_reason !== '') { ?>
						<div><strong>Причина:</strong> <?=lt_details_render_text_html($details_update_reason);?></div>
						<?php } else { ?>
						<div><strong>Последнее обновление:</strong> <?=$details_updated_label;?></div>
						<?php } ?>
					</div>
				</div>
				<?php } ?>

				<?php if ($details_file_rows && !empty($USER['id'])) { ?>
				<details class="details-files-toggle">
					<summary>Список файлов</summary>
					<div class="details-files-wrap">
						<table class="details-files-table">
							<tbody>
								<?php foreach ($details_file_rows as $fileRow) { ?>
								<tr>
									<td><?=htmlspecialchars($fileRow['name'], ENT_QUOTES, 'UTF-8');?></td>
									<td><?=htmlspecialchars($fileRow['size'], ENT_QUOTES, 'UTF-8');?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</details>
				<?php } ?>
			</section>

			<?php if ($screens) { ?>
			<section class="details-panel details-gallery-panel">
				<div class="details-gallery-grid" id="details-gallery">
					<?php foreach ($screens as $screen) { ?>
					<div
						class="details-gallery-item"
						data-details-screenshot-zoom="1"
						data-zoom-src="<?=htmlspecialchars($screen['path'], ENT_QUOTES, 'UTF-8');?>"
						title="<?=htmlspecialchars($screen['title'], ENT_QUOTES, 'UTF-8');?>"
					>
						<img src="<?=htmlspecialchars($screen['path'], ENT_QUOTES, 'UTF-8');?>" alt="<?=htmlspecialchars($screen['title'], ENT_QUOTES, 'UTF-8');?>">
					</div>
					<?php } ?>
				</div>
			</section>
			<?php } ?>

			<?php if (!empty($USER['id'])) { ?>
			<section class="details-panel details-comments-panel">
				<header class="details-comments-header">
					<h2 class="details-comments-title">Комментарии к торренту</h2>
				</header>
				<div class="details-comments-body">
					<?php listComment('torrents' , $id , 'details.php?'); ?>
				</div>
			</section>
			<?php } ?>
		</div>
	</div>
</div>
<script type="text/javascript" src="/public/js/details.js"></script>
