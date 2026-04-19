<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Функции блоков
===================================================================
*/


/*
 *	Выводим блок
*/
function render_blocks($blockfile) {
	if ($blockfile === 'block-poll.php') {
		return null;
	}

	//Проверяем файл , существует ли он
	if (file_exists ('blocks/'.$blockfile) and $blockfile != '') {
		require ('blocks/'.$blockfile);
	} else {
		echo "<center><b>[#".$blockfile."]</b>Ошибка загрузки блока</center>";
	}

	//Возвращаем null
	return null;
}


/*
 * Вывод блоков по позиции
*/
function show_blocks($position) {
	global $USER, $config, $memcached, $db , $PRIV;

	//Использовать блоки ?
	if ($config['blocks_use']) {

		//Запрос к базе
		if (false === ($orbital_blocks = $memcached->get('block_'.$position)))
		{
			$orbital_blocks = array();
			$blocks_res = $db->query("SELECT * FROM orbital_blocks
							  WHERE active = 1 AND position = '".$db->safesql($position)."'
							  ORDER BY weight ASC");
			while ($blocks_row = $db->get_row($blocks_res)) {
				$orbital_blocks[] = $blocks_row;
			}
			$memcached->set('block_'.$position, $orbital_blocks  , 0, 30 * 60 );
		}


		//Если есть блоки
		if ($orbital_blocks) {
			//Цикл
			foreach ($orbital_blocks as $block) {

				//Смотрим , где выводить блок
				$which = explode(",", $block['which']);
				// if(!$which) {
					// $which = $block['which'];
				// }
				$module_name = str_replace(".php", "", basename($_SERVER["PHP_SELF"]));
				if (!(in_array($module_name, $which) || in_array("all", $which) || (in_array("index", $which) && $module_name == "index"))) {
					continue;
				}

				//Проверяем куки
				if(!empty($_COOKIE['block_'.$block['bid']]) && $USER) {
					continue;
				}


				//Тип: всем
				if($block['type'] == 'all') {
					render_blocks($block['blockfile']);
				//Тип: гостям
				} elseif($block['type'] == 'guests' && !$USER) {
					render_blocks($block['blockfile']);
				//Тип:пользователям
				} elseif($block['type'] == 'users' && $USER) {
					render_blocks($block['blockfile']);
				}
				//Тип:модераторы
				elseif($block['type'] == 'moderators' && $PRIV['block_moderators']) {
					render_blocks($block['blockfile']);
				}
				//Тип:администраторы
				elseif($block['type'] == 'administrators' && $PRIV['block_administrators']) {
					render_blocks($block['blockfile']);
				}


			}
		}

		/*
		foreach ($orbital_blocks as $block) {

			$showed_show_hide = true;
			$bid = $block["bid"];
			$content = $block["content"];
			$title = $block["title"];
			$blockfile = $block["blockfile"];
			$bposition = $block["bposition"];
			$allow_hide = $block["allow_hide"] == 'yes';
			if ($position != $bposition)
				continue;
			$view = $block["view"];
			$which = explode(",", $block["which"]);
			$module_name = str_replace(".php", "", basename($_SERVER["PHP_SELF"]));
			if (!(in_array($module_name, $which) || in_array("all", $which) || (in_array("ihome", $which) && $module_name == "index"))) {
				continue;
			}
			if ($view == 0) {
				render_blocks($blockfile, $title, $content, $bid, $bposition, $allow_hide);
			} elseif ($view == 1 && $USER) {
				render_blocks($blockfile, $title, $content, $bid, $bposition, $allow_hide);
			} elseif ($view == 2 && (get_user_class() >= U_MODERATOR)) {
				render_blocks($blockfile, $title, $content, $bid, $bposition, $allow_hide);
			} elseif ($view == 3 && (!$USER || get_user_class() >= U_MODERATOR)) {
				render_blocks($blockfile, $title, $content, $bid, $bposition, $allow_hide);
			}
		}*/
	}
}
?>
