<?php

if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

function begin_frame($caption = "", $width = "100", $center = false, $padding = null)
{
	$bodyStyle = '';
	if ($padding !== null && is_numeric($padding)) {
		$bodyStyle = ' style="padding: '.(int) $padding.'px;"';
	}
	?>
<section class="frame">
	<?php if ($caption !== '') { ?>
	<header class="frame-header">
		<h2 class="frame-title"><?=$caption;?></h2>
	</header>
	<?php } ?>
	<div class="frame-body"<?=$bodyStyle;?>>
	<?php
}

function end_frame()
{
	?>
	</div>
</section>
	<?php
}

?>
