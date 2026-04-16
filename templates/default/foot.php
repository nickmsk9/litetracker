<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

$showStandardSidebar = (!defined('LIGHT') && !empty($GLOBALS['LITETRACKER_STANDARD_SIDEBAR']));
?>
			</div>

			<div class="site-bottom-blocks">
				<?php show_blocks('d'); ?>
			</div>
		</main>

		<?php if ($showStandardSidebar) { ?>
			<?php render_standard_sidebar(); ?>
		<?php } ?>
	</div>
</div>

<div class="site-footer-band">
	<div class="site-shell site-shell-band">
		<footer class="site-footer">
			<div class="site-footer-copy">
				<noindex><?php echo LITETRACKER_COPYRIGHT; ?></noindex>
			</div>
		</footer>
	</div>
</div>
</body>
</html>
