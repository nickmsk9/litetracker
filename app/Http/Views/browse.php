<?php
if (!defined('CMS')) {
	die('Direct access denied.');
}
?>
<div class="browse-page" data-browse-page>
	<section class="browse-hero">
		<div class="browse-hero-copy">
			<h1 class="browse-hero-title">Торренты</h1>
		</div>
		<?php if ($canUpload) { ?>
		<div class="browse-hero-action">
			<a class="browse-upload-button" href="upload.php">
				<span class="browse-upload-button-icon" aria-hidden="true">
					<svg viewBox="0 0 20 20" fill="none">
						<path d="M10 13V4m0 0L6.75 7.25M10 4l3.25 3.25M4 14.5v.5A1 1 0 0 0 5 16h10a1 1 0 0 0 1-1v-.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</span>
				<span>Загрузить торрент</span>
			</a>
		</div>
		<?php } ?>
	</section>

	<div class="browse-layout">
		<div class="browse-main">
			<section class="browse-panel browse-search-panel">
				<form action="browse.php" method="get" class="browse-search-form" data-browse-search-form>
					<?php if ($id_category > 0) { ?>
					<input type="hidden" name="id_category" value="<?=$id_category;?>">
					<?php } ?>
					<?php if ($activeTag !== '') { ?>
					<input type="hidden" name="tag" value="<?=htmlspecialchars($activeTag, ENT_QUOTES, 'UTF-8');?>">
					<?php } ?>
					<input type="hidden" name="view" value="<?=htmlspecialchars($view, ENT_QUOTES, 'UTF-8');?>" data-browse-view-input>
					<?php if ($quickFilters['status'] !== '') { ?><input type="hidden" name="status" value="<?=htmlspecialchars($quickFilters['status'], ENT_QUOTES, 'UTF-8');?>"><?php } ?>
					<?php if ($quickFilters['with_screens']) { ?><input type="hidden" name="with_screens" value="1"><?php } ?>
					<?php if ($quickFilters['freeleech']) { ?><input type="hidden" name="freeleech" value="1"><?php } ?>
					<?php if ($quickFilters['bookmarked']) { ?><input type="hidden" name="bookmarked" value="1"><?php } ?>
					<?php if ($quickFilters['completed']) { ?><input type="hidden" name="completed" value="1"><?php } ?>
					<?php if ($quickFilters['alive']) { ?><input type="hidden" name="alive" value="alive"><?php } ?>
					<?php if ($quickFilters['dead']) { ?><input type="hidden" name="alive" value="dead"><?php } ?>
					<?php foreach ($selectedFilters as $group => $values) { ?>
						<?php foreach ($values as $value) { ?>
						<input type="hidden" name="filter_<?=$group;?>[]" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>">
						<?php } ?>
					<?php } ?>
					<div class="browse-search-row">
						<input type="text" name="search" value="<?=htmlspecialchars($search, ENT_QUOTES, 'UTF-8');?>" class="browse-search-input" placeholder="Поиск..." autocomplete="off" data-browse-search-input>
						<button type="submit" class="browse-search-submit">Найти</button>
					</div>
					<div class="browse-search-suggest" data-browse-suggest hidden></div>
				</form>
			</section>

			<?php if ($categories) { ?>
			<nav class="browse-panel browse-categories" aria-label="Категории торрентов">
				<a class="browse-category-tab<?=($id_category === 0 ? ' is-active' : '');?>" href="<?=htmlspecialchars(browse_build_url(array('id_category' => null, 'page' => null)), ENT_QUOTES, 'UTF-8');?>">Все торренты</a>
				<?php foreach ($categories as $category) { ?>
				<a class="browse-category-tab<?=($id_category === (int) $category['id'] ? ' is-active' : '');?>" href="<?=htmlspecialchars(browse_build_url(array('id_category' => (int) $category['id'], 'page' => null)), ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8');?></a>
				<?php } ?>
			</nav>
			<?php } ?>

			<?php if ($popularTags) { ?>
			<nav class="browse-panel browse-tag-cloud" aria-label="Популярные теги">
				<div class="browse-tag-cloud-title">Популярные теги</div>
				<div class="browse-tag-cloud-list">
					<?php foreach ($popularTags as $popularTag) { ?>
					<?php
					$tagName = (string) ($popularTag['name'] ?? '');
					if ($tagName === '') {
						continue;
					}
					$tagCount = (int) ($popularTag['count'] ?? 0);
					$isActiveTag = (function_exists('mb_strtolower') ? mb_strtolower($tagName, 'UTF-8') === mb_strtolower($activeTag, 'UTF-8') : strtolower($tagName) === strtolower($activeTag));
					?>
					<a class="browse-tag-chip<?=($isActiveTag ? ' is-active' : '');?>" href="<?=htmlspecialchars(browse_build_url(array('tag' => $tagName, 'page' => null)), ENT_QUOTES, 'UTF-8');?>" rel="tag">
						<span><?=htmlspecialchars($tagName, ENT_QUOTES, 'UTF-8');?></span>
						<?php if ($tagCount > 0) { ?><span class="browse-tag-count"><?=$tagCount;?></span><?php } ?>
					</a>
					<?php } ?>
				</div>
			</nav>
			<?php } ?>

			<section class="browse-panel browse-results-panel" data-browse-results-panel>
				<div class="home-browse-toolbar">
					<ul class="browse-sort-list" role="tablist" aria-label="Сортировка торрентов">
						<?php foreach ($sortOptions as $sortKey => $sortOption) { ?>
						<li class="browse-sort-item<?=($sort === $sortKey ? ' is-active' : '');?>">
							<a class="browse-sort-link" href="<?=htmlspecialchars(browse_build_url(array('sort' => $sortKey, 'page' => null)), ENT_QUOTES, 'UTF-8');?>"><?=$sortOption['label'];?></a>
						</li>
						<?php } ?>
					</ul>

					<div class="browse-view-switch" role="group" aria-label="Вид списка">
						<button type="button" class="browse-view-button<?=($view === 'full' ? ' is-active' : '');?>" data-browse-view-toggle data-browse-view="full" aria-pressed="<?=($view === 'full' ? 'true' : 'false');?>">
							<span class="browse-view-icon browse-view-icon-medium" aria-hidden="true"></span>
						</button>
						<button type="button" class="browse-view-button<?=($view === 'compact' ? ' is-active' : '');?>" data-browse-view-toggle data-browse-view="compact" aria-pressed="<?=($view === 'compact' ? 'true' : 'false');?>">
							<span class="browse-view-icon browse-view-icon-small" aria-hidden="true"></span>
						</button>
					</div>
				</div>

				<?php if ($rows) { ?>
				<div class="browse-torrent-list" data-browse-list data-view="<?=$view;?>">
					<?php foreach ($rows as $row) { ?>
					<?php
					$torrentId = (int) $row['id'];
					$category = (!empty($categoriesById[(int) $row['id_category']]) ? $categoriesById[(int) $row['id_category']] : array('id' => 0, 'name' => 'Без категории', 'image' => ''));
					$user = (array) ($torrentAuthorsById[(int) $row['id_user']] ?? array());
					$torrentCard = lt_torrent_prepare_browse_card($row, $category, $user, $torrentAuthorPrivilegesByClass);
					?>
					<?php include lt_templates_path('default/tpl.torrent.card.php'); ?>
					<?php } ?>
				</div>
				<?php } else { ?>
				<div class="browse-empty-state">Торренты не найдены.</div>
				<?php } ?>
			</section>
			<?php if ($rows) { ?>
			<div class="browse-pagination" data-browse-pagination><?=$pagerbottom ?: $pagertop;?></div>
			<?php } ?>
		</div>

		<aside class="browse-sidebar" data-browse-sidebar>
			<form action="browse.php" method="get" class="browse-filter-panel" data-browse-filter-form>
				<?php if ($search !== '') { ?>
				<input type="hidden" name="search" value="<?=htmlspecialchars($search, ENT_QUOTES, 'UTF-8');?>">
				<?php } ?>
				<?php if ($activeTag !== '') { ?>
				<input type="hidden" name="tag" value="<?=htmlspecialchars($activeTag, ENT_QUOTES, 'UTF-8');?>">
				<?php } ?>
				<?php if ($id_category > 0) { ?>
				<input type="hidden" name="id_category" value="<?=$id_category;?>">
				<?php } ?>
				<input type="hidden" name="view" value="<?=htmlspecialchars($view, ENT_QUOTES, 'UTF-8');?>" data-browse-view-input>

				<fieldset class="browse-filter-group">
					<legend class="browse-filter-title">Быстрые фильтры:</legend>
					<div class="browse-filter-options">
						<label class="browse-filter-option">
							<input type="checkbox" name="with_screens" value="1"<?=($quickFilters['with_screens'] ? ' checked' : '');?> data-browse-auto-filter>
							<span>Со скриншотами</span>
						</label>
						<label class="browse-filter-option">
							<input type="checkbox" name="completed" value="1"<?=($quickFilters['completed'] ? ' checked' : '');?> data-browse-auto-filter>
							<span>Завершённые</span>
						</label>
						<?php if (!empty($USER['id'])) { ?>
						<label class="browse-filter-option">
							<input type="checkbox" name="bookmarked" value="1"<?=($quickFilters['bookmarked'] ? ' checked' : '');?> data-browse-auto-filter>
							<span>В закладках</span>
						</label>
						<?php } ?>
						<label class="browse-filter-option">
							<input type="checkbox" name="freeleech" value="1"<?=($quickFilters['freeleech'] ? ' checked' : '');?> data-browse-auto-filter>
							<span>Freeleech</span>
						</label>
						<label class="browse-filter-option">
							<select name="alive" class="browse-filter-select" data-browse-auto-filter>
								<option value="">Живые и мёртвые</option>
								<option value="alive"<?=($quickFilters['alive'] ? ' selected' : '');?>>Только живые</option>
								<option value="dead"<?=($quickFilters['dead'] ? ' selected' : '');?>>Только мёртвые</option>
							</select>
						</label>
						<label class="browse-filter-option">
							<select name="status" class="browse-filter-select" data-browse-auto-filter>
								<option value="">Любой статус</option>
								<option value="approved"<?=($quickFilters['status'] === 'approved' ? ' selected' : '');?>>Опубликовано</option>
								<option value="pending"<?=($quickFilters['status'] === 'pending' ? ' selected' : '');?>>Ожидает модерации</option>
								<option value="need_fix"<?=($quickFilters['status'] === 'need_fix' ? ' selected' : '');?>>Нужна доработка</option>
								<option value="hidden"<?=($quickFilters['status'] === 'hidden' ? ' selected' : '');?>>Скрыто</option>
								<option value="deleted"<?=($quickFilters['status'] === 'deleted' ? ' selected' : '');?>>Удалено</option>
							</select>
						</label>
					</div>
				</fieldset>

				<?php foreach ($schema as $group => $definition) { ?>
				<?php
				list($visibleOptions, $hiddenOptions) = browse_filter_options_split($definition['options'], $selectedFilters[$group], 4);
				$hiddenSelectedCount = 0;
				foreach (array_keys($hiddenOptions) as $hiddenValue) {
					if (in_array($hiddenValue, $selectedFilters[$group], true)) {
						$hiddenSelectedCount++;
					}
				}
				$showMoreLabel = 'Показать ещё '.count($hiddenOptions);
				?>
				<fieldset class="browse-filter-group">
					<legend class="browse-filter-title"><?=$definition['label'];?>:</legend>
					<div class="browse-filter-options">
						<?php foreach ($visibleOptions as $value => $label) { ?>
						<label class="browse-filter-option">
							<input type="checkbox" name="filter_<?=$group;?>[]" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>"<?=(in_array($value, $selectedFilters[$group], true) ? ' checked' : '');?>>
							<span><?=htmlspecialchars($label, ENT_QUOTES, 'UTF-8');?></span>
						</label>
						<?php } ?>
					</div>
					<?php if ($hiddenOptions) { ?>
					<details class="browse-filter-more"<?=(($hiddenSelectedCount > 0) ? ' open' : '');?>>
						<summary class="browse-filter-more-toggle" data-closed-label="<?=htmlspecialchars($showMoreLabel, ENT_QUOTES, 'UTF-8');?>" data-open-label="Скрыть"><?=$hiddenSelectedCount > 0 ? 'Скрыть' : $showMoreLabel;?></summary>
						<div class="browse-filter-options browse-filter-options-extra">
							<?php foreach ($hiddenOptions as $value => $label) { ?>
							<label class="browse-filter-option">
								<input type="checkbox" name="filter_<?=$group;?>[]" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>"<?=(in_array($value, $selectedFilters[$group], true) ? ' checked' : '');?>>
								<span><?=htmlspecialchars($label, ENT_QUOTES, 'UTF-8');?></span>
							</label>
							<?php } ?>
						</div>
					</details>
					<?php } ?>
				</fieldset>
				<?php } ?>

				<button type="submit" class="browse-filter-submit">Применить</button>
			</form>
		</aside>
	</div>
</div>

