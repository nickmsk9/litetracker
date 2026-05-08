<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

$detailsInfoTitle = lt_details_info_heading($cat_name_plain);
$detailsRatingPercent = max(0, min(100, ($details_rating_score / 5) * 100));
$detailsTitle = htmlspecialchars((string) ($torrent_name_plain ?? ''), ENT_QUOTES, 'UTF-8');
$detailsDescriptionHtml = trim((string) $details_description_html);
if ($detailsDescriptionHtml === '' && !$details_has_structured_content) {
	$detailsDescriptionHtml = trim((string) $descr);
}
$detailsDescriptionHtml = ($detailsDescriptionHtml !== '' ? cleanhtml($detailsDescriptionHtml) : '');
$detailsCategoryHref = 'browse.php?id_category='.(int) ($cat_id ?? 0);
$detailsCategoryLabel = htmlspecialchars((string) ($cat_name_plain ?? ''), ENT_QUOTES, 'UTF-8');
$detailsPosterPath = trim((string) $image);
$detailsPosterAvailable = ($detailsPosterPath !== '' && stripos($detailsPosterPath, 'default_avatar.gif') === false);
?>

<div class="details-page">
	<div class="details-layout">
		<aside class="details-sidebar">
			<div class="details-poster-card lt-card">
				<div class="details-poster-badge"><?=htmlspecialchars($category_badge !== '' ? $category_badge : 'торрент', ENT_QUOTES, 'UTF-8');?></div>
				<?php if ($detailsPosterAvailable) { ?>
				<img class="details-poster-image" src="<?=htmlspecialchars($detailsPosterPath, ENT_QUOTES, 'UTF-8');?>" alt="<?=$detailsTitle;?>">
				<?php } else { ?>
				<div class="details-media-placeholder details-poster-placeholder" role="img" aria-label="Постер отсутствует">Постер отсутствует</div>
				<?php } ?>
			</div>

			<div class="details-sidebar-actions lt-card">
				<?php if ($details_download_href !== '') { ?>
				<div class="details-download-group">
					<a class="details-download-button lt-btn lt-btn-primary" href="<?=htmlspecialchars($details_download_href, ENT_QUOTES, 'UTF-8');?>"><?=$language['details_2'];?></a>
					<?php if ($details_magnet_href !== '') { ?>
					<a class="details-download-button details-download-button-magnet lt-btn lt-btn-primary" href="<?=htmlspecialchars($details_magnet_href, ENT_QUOTES, 'UTF-8');?>" aria-label="<?=$language['details_3'];?>">m</a>
					<?php } ?>
				</div>
				<?php } elseif ($details_guest_login_href !== '' || $details_guest_register_href !== '') { ?>
				<div class="details-guest-box">
					<div class="details-guest-copy"><?=htmlspecialchars($details_guest_notice, ENT_QUOTES, 'UTF-8');?></div>
					<div class="details-guest-actions">
						<?php if ($details_guest_register_href !== '') { ?>
						<a class="details-download-button lt-btn lt-btn-primary" href="<?=htmlspecialchars($details_guest_register_href, ENT_QUOTES, 'UTF-8');?>">Зарегистрироваться</a>
						<?php } ?>
						<?php if ($details_guest_login_href !== '') { ?>
						<a class="details-bookmark-button details-guest-login-button lt-btn lt-btn-secondary" href="<?=htmlspecialchars($details_guest_login_href, ENT_QUOTES, 'UTF-8');?>">Войти</a>
						<?php } ?>
					</div>
				</div>
				<?php } ?>

				<?php if ($details_bookmark_href !== '') { ?>
				<a
					class="details-bookmark-button lt-btn lt-btn-secondary<?=(!empty($details_bookmarked) ? ' details-bookmark-button-active' : '');?>"
					href="<?=htmlspecialchars($details_bookmark_href, ENT_QUOTES, 'UTF-8');?>"
					data-details-bookmark="1"
					data-bookmarked="<?=(!empty($details_bookmarked) ? '1' : '0');?>"
				><?=htmlspecialchars($details_bookmark_label, ENT_QUOTES, 'UTF-8');?></a>
				<?php } ?>

				<?php if ($details_edit_href !== '') { ?>
				<a class="details-edit-link lt-btn lt-btn-secondary" href="<?=htmlspecialchars($details_edit_href, ENT_QUOTES, 'UTF-8');?>">Редактировать релиз</a>
				<?php } ?>
			</div>
		</aside>

		<div class="details-main">
			<section class="details-panel details-title-panel lt-card">
				<h1 class="details-title"><?=$detailsTitle;?></h1>
				<div class="details-title-subline">
					<a class="details-category-link" href="<?=htmlspecialchars($detailsCategoryHref, ENT_QUOTES, 'UTF-8');?>"><?=$detailsCategoryLabel;?></a>
				</div>
				<?php if ($details_status_badges) { ?>
				<div class="details-badges">
					<?php foreach ($details_status_badges as $badge) { ?>
					<span class="details-badge<?=(!empty($badge['class']) ? ' '.htmlspecialchars($badge['class'], ENT_QUOTES, 'UTF-8') : '');?>"><?=htmlspecialchars($badge['label'], ENT_QUOTES, 'UTF-8');?></span>
					<?php } ?>
				</div>
				<?php } ?>
			</section>

			<?php if (!$infohash) { ?>
			<div class="details-panel details-alert-panel lt-card">
				Этот релиз пока нельзя скачать: у него нет torrent-файла.
			</div>
			<?php } ?>

			<section class="details-panel details-meta-panel lt-card">
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

				<div class="details-meta-grid">
					<div class="details-meta-cell">
						<div class="details-meta-cell-label">Категория</div>
						<div class="details-meta-cell-value"><a class="details-category-link" href="<?=htmlspecialchars($detailsCategoryHref, ENT_QUOTES, 'UTF-8');?>"><?=$detailsCategoryLabel;?></a></div>
					</div>
					<div class="details-meta-cell">
						<div class="details-meta-cell-label">Размер</div>
						<div class="details-meta-cell-value"><?=$size;?></div>
					</div>
					<div class="details-meta-cell">
						<div class="details-meta-cell-label">Сиды</div>
						<div class="details-meta-cell-value"><?=$seeders;?></div>
					</div>
					<div class="details-meta-cell">
						<div class="details-meta-cell-label">Личи</div>
						<div class="details-meta-cell-value"><?=$leechers;?></div>
					</div>
					<div class="details-meta-cell">
						<div class="details-meta-cell-label">Скачивания</div>
						<div class="details-meta-cell-value"><?=$completed;?></div>
					</div>
					<div class="details-meta-cell">
						<div class="details-meta-cell-label">Создан</div>
						<div class="details-meta-cell-value"><?=$details_created_label;?></div>
					</div>
					<div class="details-meta-cell">
						<div class="details-meta-cell-label">Обновлён</div>
						<div class="details-meta-cell-value"><?=$details_updated_label;?></div>
					</div>
					<div class="details-meta-cell">
						<div class="details-meta-cell-label">Автор</div>
						<div class="details-meta-cell-value"><a class="details-user-link" href="<?=profile_href($user);?>"><?=get_user_color((int) $user_class, htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'), $user);?></a></div>
					</div>
				</div>

			<div class="details-reactions-row">
				<div class="yt-reactions" id="details-plus-reactions">
					<?php if (!empty($USER['id']) && lt_user_has_plus($USER)) { ?>
					<a class="yt-reaction-btn yt-reaction-like<?=($details_plus_reaction_stats['user'] === 'like' ? ' yt-reaction-active' : '');?>" href="details.php?id=<?=(int) $id;?>&amp;plus_reaction=like&amp;<?=$details_plus_reaction_csrf;?>" title="Лайк"><svg class="yt-reaction-icon" viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M1 8a1 1 0 0 1 1-1h2v7H2a1 1 0 0 1-1-1V8zm4-1v7l.6.4A5 5 0 0 0 8.5 15h4.07a1.5 1.5 0 0 0 1.47-1.21l.9-4.5A1.5 1.5 0 0 0 13.57 7H11V4.5A1.5 1.5 0 0 0 9.5 3h-.25a.75.75 0 0 0-.75.75V5a3 3 0 0 1-.9 2.13L5 9z"/></svg><?=$details_plus_reaction_stats['like'];?></a>
					<span class="yt-reaction-sep"></span>
					<a class="yt-reaction-btn yt-reaction-dislike<?=($details_plus_reaction_stats['user'] === 'dislike' ? ' yt-reaction-active' : '');?>" href="details.php?id=<?=(int) $id;?>&amp;plus_reaction=dislike&amp;<?=$details_plus_reaction_csrf;?>" title="Дизлайк"><svg class="yt-reaction-icon" viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M1 10a1 1 0 0 0 1 1h2V4H2a1 1 0 0 0-1 1v5zm4 1V4l.6-.4A5 5 0 0 1 8.5 3h4.07a1.5 1.5 0 0 1 1.47 1.21l.9 4.5A1.5 1.5 0 0 1 13.57 11H11v2.5A1.5 1.5 0 0 1 9.5 15h-.25a.75.75 0 0 1-.75-.75V13a3 3 0 0 0-.9-2.13L5 9z"/></svg><?=$details_plus_reaction_stats['dislike'];?></a>
					<?php } else { ?>
					<span class="yt-reaction-btn yt-reaction-like"><svg class="yt-reaction-icon" viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M1 8a1 1 0 0 1 1-1h2v7H2a1 1 0 0 1-1-1V8zm4-1v7l.6.4A5 5 0 0 0 8.5 15h4.07a1.5 1.5 0 0 0 1.47-1.21l.9-4.5A1.5 1.5 0 0 0 13.57 7H11V4.5A1.5 1.5 0 0 0 9.5 3h-.25a.75.75 0 0 0-.75.75V5a3 3 0 0 1-.9 2.13L5 9z"/></svg><?=$details_plus_reaction_stats['like'];?></span>
					<span class="yt-reaction-sep"></span>
					<span class="yt-reaction-btn yt-reaction-dislike"><svg class="yt-reaction-icon" viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M1 10a1 1 0 0 0 1 1h2V4H2a1 1 0 0 0-1 1v5zm4 1V4l.6-.4A5 5 0 0 1 8.5 3h4.07a1.5 1.5 0 0 1 1.47 1.21l.9 4.5A1.5 1.5 0 0 1 13.57 11H11v2.5A1.5 1.5 0 0 1 9.5 15h-.25a.75.75 0 0 1-.75-.75V13a3 3 0 0 0-.9-2.13L5 9z"/></svg><?=$details_plus_reaction_stats['dislike'];?></span>
					<?php } ?>
					<a class="yt-reaction-list-link" href="details.php?id=<?=(int) $id;?>&amp;reaction_list=1" data-plus-reaction-list="1" data-reaction-object-type="torrent" data-reaction-object-id="<?=(int) $id;?>">Кто оценил</a>
				</div>
			</div>
			</section>

			<?php if (!empty($details_tracker_rows)) { ?>
			<section class="details-panel details-trackers-panel lt-card">
				<div class="details-section-group">
					<div class="details-trackers-heading">
						<h2 class="details-section-title">Мультитрекерная раздача</h2>
						<?php if ($details_tracker_update_href !== '') { ?>
						<a
							class="details-tracker-refresh-button"
							href="<?=htmlspecialchars($details_tracker_update_href, ENT_QUOTES, 'UTF-8');?>"
							data-details-trackers-refresh="1"
						>Обновить</a>
						<?php } ?>
					</div>
					<div class="details-tracker-summary">
						<span>Внешних трекеров: <?=number_format((int) $details_external_tracker_count);?></span>
						<span>Пиры в списках учитывают локальный и внешний announce.</span>
					</div>
					<div class="details-trackers-wrap">
						<table class="details-trackers-table">
							<thead>
								<tr>
									<th>Трекер</th>
									<th>Раздают</th>
									<th>Качают</th>
									<th>Проверка</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($details_tracker_rows as $trackerRow) { ?>
								<tr>
									<td>
										<div class="details-tracker-url"><?=htmlspecialchars((string) ($trackerRow['tracker'] ?? ''), ENT_QUOTES, 'UTF-8');?></div>
										<?php if (!empty($trackerRow['state'])) { ?>
										<div class="details-tracker-state"><?=htmlspecialchars((string) $trackerRow['state'], ENT_QUOTES, 'UTF-8');?></div>
										<?php } ?>
									</td>
									<td><?=htmlspecialchars((string) ($trackerRow['seeders'] ?? '0'), ENT_QUOTES, 'UTF-8');?></td>
									<td><?=htmlspecialchars((string) ($trackerRow['leechers'] ?? '0'), ENT_QUOTES, 'UTF-8');?></td>
									<td><?=htmlspecialchars((string) ($trackerRow['lastchecked'] ?? ''), ENT_QUOTES, 'UTF-8');?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</section>
			<?php } ?>

			<section class="details-panel details-info-panel lt-card">
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
					<div class="details-description-block lt-card">
						<div class="details-section-title">Описание</div>
						<div class="details-copy-block"><?=$detailsDescriptionHtml;?></div>
					</div>
					<?php } ?>

					<?php if ($details_summary_text !== '') { ?>
					<div class="details-summary-box">
						<div class="details-summary-title">Краткое содержание</div>
						<div class="details-summary-text"><?=htmlspecialchars($details_summary_text, ENT_QUOTES, 'UTF-8');?></div>
					</div>
					<?php } ?>

					<?php if (!empty($USER['id']) && lt_user_has_plus($USER) && ($details_summary_text !== '' || $detailsDescriptionHtml !== '')) { ?>
					<button class="details-tts-button lt-btn lt-btn-ghost" type="button" data-details-tts>Озвучить пост</button>
					<div data-details-tts-text hidden><?=htmlspecialchars($details_summary_text !== '' ? $details_summary_text : strip_tags($detailsDescriptionHtml), ENT_QUOTES, 'UTF-8');?></div>
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

			<section class="details-panel details-gallery-panel lt-card">
				<h2 class="details-section-title">Скриншоты</h2>
				<?php if ($screens) { ?>
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
				<?php } else { ?>
				<div class="details-media-placeholder details-screens-placeholder">Скриншоты отсутствуют</div>
				<?php } ?>
			</section>

			<?php if (!empty($USER['id'])) { ?>
			<section class="details-panel details-comments-panel lt-card">
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
