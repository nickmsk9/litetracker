<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

begin_frame('Категории');
echo '<div class="sidebar-list">'.$categories.'</div>';
end_frame();
?>
