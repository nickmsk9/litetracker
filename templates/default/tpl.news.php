<?php
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Новостной системы
////////////////////////////////////////////////////////

begin_frame('', '100', false, 0);
?>
<div class="news-detail-page">
	<article class="news-detail-card">
		<header class="news-detail-header">
			<h1 class="news-detail-title"><?=$name;?></h1>
			<div class="news-detail-date"><?=htmlspecialchars((string) ($published_at ?? $date), ENT_QUOTES, 'UTF-8');?></div>
		</header>

		<div class="news-detail-content"><?=$text;?></div>

		<div class="news-detail-footer">
			<div class="news-detail-publisher-block">
				<span class="news-detail-publisher-label"><?=$language['news_15'];?>:</span>
				<a class="news-detail-publisher-link" href="<?=profile_href($user_id);?>"><?=get_user_color($user_class , htmlspecialchars((string) $user_name, ENT_QUOTES, 'UTF-8'));?></a>
			</div>

			<?php if (!empty($PRIV['news_add'])) { ?>
			<div class="news-detail-admin-links">
				<a class="news-detail-admin-link lt-btn lt-btn-secondary" href="news.php?act=edit&id=<?=$id;?>"><?=$language['news_16'];?></a>
				<a class="news-detail-admin-link lt-btn lt-btn-danger" href="news.php?act=delete&id=<?=$id;?>" onclick="return confirm('Удалить эту новость?');"><?=$language['news_17'];?></a>
			</div>
			<?php } ?>
		</div>
	</article>
</div>
<?php
end_frame();
?>

<?php
if(defined('NEWS_DETAILS') ) {
	//Комментарии
	begin_frame($language['comments_13']);
	listComment('news' , $id , 'news.php?id='.$id.'&');
	end_frame();
}
?>


