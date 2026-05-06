<?php
require 'system/init.php';

header('HTTP/1.0 404 Not Found');
head('404');
begin_frame('404');
?>
<div class="lt-404-page">
	<div class="lt-404-code">404</div>
	<div class="lt-404-title">Ой... страничка потерялась.</div>
	<div class="lt-404-actions">
		<a class="lt-404-home-link" href="index.php">Перейти на главную</a>
	</div>
	<div class="lt-404-pixel-wrap" aria-hidden="true">
		<img class="lt-404-pixel-art" src="templates/<?=$config['template'];?>/images/forgithub.png" alt="" width="200">
	</div>
</div>
<?php
end_frame();
foot();
?>
