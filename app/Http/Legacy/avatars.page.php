<?php
require 'system/init.php';
require_once __DIR__.'/static_pages.php';

$letters = array('#', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
$series = array(
	'#' => array('12 Kingdoms', '3x3 Eyes'),
	'A' => array('Ah! My Goddess', 'Air', 'Air Gear', 'Angel Sanctuary', 'Animatrix', 'Appleseed'),
	'B' => array('Basilisk', 'Beck', 'Berserk', 'Black Lagoon', 'Bleach', 'Blood+'),
	'C' => array('Chobits', 'Chrono Crusade', 'Claymore', 'Code Geass', 'Cowboy Bebop'),
	'D' => array('D.Gray-man', 'Death Note'),
	'E' => array('Elfen Lied', 'Ergo Proxy', 'Evangelion'),
	'F' => array('FLCL', 'Fate/stay night', 'Final Fantasy', 'Fullmetal Alchemist'),
	'G' => array('GTO', 'Ghost in the Shell', 'Gundam', 'Gungrave'),
	'H' => array('Haibane Renmei', 'Hellsing', 'Higurashi no Naku Koro Ni'),
	'I' => array('Ikkitousen', 'Inu-Yasha'),
	'J' => array('Jungle wa Itsumo Hare nochi Guu'),
	'K' => array('Kanon', 'Karas', 'Kare Kano', 'Kenshin', 'Kino no Tabi'),
	'L' => array('Lain', 'Last Exile', 'Love Hina', 'Lucky Star'),
	'M' => array('Mahoromatic', 'Mai Hime', 'Maria-sama', 'My Neighbor Totoro'),
	'N' => array('Nana', 'Naruto', 'Naruto Shippuuden', 'Noein'),
	'O' => array('One Piece', 'Ouran Host Club'),
	'P' => array('Paprika', 'Paradise Kiss', 'Peacemaker'),
	'R' => array('Read or Die', 'Rozen Maiden'),
	'S' => array('Sailor Moon', 'Samurai Champloo', 'School Rumble', 'Slayers', 'Spice and Wolf'),
	'T' => array('Tales of Phantasia', 'Tenjou Tenge', 'Trigun', 'Tsubasa Reservoir Chronicle'),
	'U' => array('Utawarerumono', 'Utena'),
	'V' => array('Vampire Princess Miyu', 'Vandread', 'Vision of Escaflowne'),
	'W' => array('Welcome to the NHK!', 'Witch Hunter Robin', 'Wolf’s Rain'),
	'X' => array('X', 'xxxHOLiC'),
	'Y' => array('Yami no Matsuei', 'Yu Yu Hakusho', 'Yu-Gi-Oh!'),
	'Z' => array('Zero no Tsukaima'),
);

head('Аватары');
begin_frame('Аватары');
?>
<div class="static-page static-avatars">
	<nav class="static-alpha">
		<?php foreach ($letters as $letter) { ?>
		<a href="#letter-<?=htmlspecialchars($letter === '#' ? 'num' : strtolower($letter), ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars($letter, ENT_QUOTES, 'UTF-8');?></a>
		<?php } ?>
	</nav>

	<?php foreach ($series as $letter => $items) { ?>
	<h3 id="letter-<?=htmlspecialchars($letter === '#' ? 'num' : strtolower($letter), ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars($letter, ENT_QUOTES, 'UTF-8');?></h3>
	<ul class="static-columns">
		<?php foreach ($items as $item) { ?>
		<li><?=htmlspecialchars($item, ENT_QUOTES, 'UTF-8');?></li>
		<?php } ?>
	</ul>
	<?php } ?>
</div>
<?php
end_frame();
foot();
