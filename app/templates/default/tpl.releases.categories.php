<?php
if (!defined('LITETRACKER'))
	die('Direct access denied.');
?>
<a class="category-card" href="browse.php?id_category=<?=$id;?>">
	<span class="category-card-name"><?=$name;?></span>
	<span class="category-card-meta"><?=$count;?> релизов · <?=$size;?></span>
</a>
