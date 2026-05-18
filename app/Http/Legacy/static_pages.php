<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

function lt_static_page_render($title, $sections)
{
	head($title);
	begin_frame($title);
	?>
	<div class="static-page">
		<?php foreach ($sections as $section) { ?>
			<?php if (!empty($section['title'])) { ?>
			<h3><?=htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8');?></h3>
			<?php } ?>

			<?php if (!empty($section['text'])) { ?>
			<p><?=$section['text'];?></p>
			<?php } ?>

			<?php if (!empty($section['items'])) { ?>
			<ul>
				<?php foreach ($section['items'] as $item) { ?>
				<li><?=$item;?></li>
				<?php } ?>
			</ul>
			<?php } ?>
		<?php } ?>
	</div>
	<?php
	end_frame();
	foot();
}
