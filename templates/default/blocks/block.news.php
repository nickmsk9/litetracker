<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

begin_frame('<a class="frame-title-link" href="news.php">Последние новости</a>');

echo '<div class="sidebar-news-list">';

foreach ($news_array as $arr) {
	$title = lt_fix_utf8_mojibake((string) $arr['name']);
	$text = trim(strip_tags(cleanhtml(lt_fix_utf8_mojibake((string) $arr['text']))));

	if (mb_strlen($title) > 56) {
		$title = mb_substr($title, 0, 56).'...';
	}

	if (mb_strlen($text) > 180) {
		$text = mb_substr($text, 0, 180).'...';
	}

	?>
	<article class="sidebar-news-item">
		<a class="sidebar-news-title" href="news.php?id=<?=$arr['id'];?>" rel="bookmark" title="<?=$language['news_18'];?>"><?=htmlspecialchars($title, ENT_QUOTES, 'UTF-8');?></a>
		<div class="sidebar-news-date"><?=convent_date($arr['date']);?></div>
		<div class="sidebar-news-excerpt"><?=htmlspecialchars($text, ENT_QUOTES, 'UTF-8');?></div>
	</article>
	<?php
}

echo '</div>';

end_frame();
?>
