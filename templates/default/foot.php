<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

$showStandardSidebar = (!defined('LIGHT') && !empty($GLOBALS['LITETRACKER_STANDARD_SIDEBAR']) && empty($GLOBALS['LITETRACKER_HIDE_STANDARD_SIDEBAR']));
$showBottomBlocks = empty($GLOBALS['LITETRACKER_HIDE_BOTTOM_BLOCKS']);
?>
			</div>

			<?php if ($showBottomBlocks) { ?>
			<div class="site-bottom-blocks">
				<?php show_blocks('d'); ?>
			</div>
			<?php } ?>
		</main>

		<?php if ($showStandardSidebar) { ?>
			<?php render_standard_sidebar(); ?>
		<?php } ?>
	</div>
</div>
</div>

<div style="clear: both;"></div>
<footer class="footer">
	<div class="wrapper pd20 clearfix">
		<div class="pull-left mr20">
			<a href="/" class="logo">LiteTracker</a>
		</div>

		<div class="pull-left">
			<div>LiteTracker &copy; 2026</div>

			<div class="clearfix">
				<div class="pull-left mr40"><a href="/disclaimer.php" class="u">Пользовательское соглашение</a></div>
				<div class="pull-left mr40"><a href="/complaint.php" class="u">Правообладателям</a></div>
				<div class="pull-left mr40"><a href="/avatars.php" class="u">Аватары</a></div>
				<div class="pull-left mr40"><a href="/faq.php" class="u">FAQ</a></div>
				<div class="pull-left mr40"><a href="/rules.php" class="u">Правила</a></div>
				<div class="pull-left"><a href="/feedback.php" class="u">Обратная связь</a></div>
			</div>
		</div>
	</div>

	<hr class="m0">

	<div class="wrapper pd20 clearfix">
		<div class="adults-only pull-left mr20">
			<i class="s-icons-18plus iblock pull-left mr20"></i>
			<div class="oh">Сайт может содержать материалы не&nbsp;предназначенные для лиц младше 18&nbsp;лет.</div>
		</div>

		<div class="social-links pull-right clearfix">
			<a target="_blank" href="https://www.facebook.com/animelayer" class="iblock pull-left mr10 s-icons-facebook"></a>
			<a target="_blank" href="https://twitter.com/animelayer" class="iblock pull-left mr10 s-icons-twitter"></a>
			<a target="_blank" href="/rss/" class="iblock pull-left s-icons-rss"></a>
		</div>
	</div>
</footer>
</div>

</body>
</html>
