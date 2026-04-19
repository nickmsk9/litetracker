<?php
/*
===================================================================
-------------------------------------------------------------------
Назначение: Функции тегов
===================================================================
*/

function get_tags()
{
	global $memcached, $db;

	$arr = array();

	$res = false;
	if (isset($memcached) && is_object($memcached)) {
		$res = $memcached->get('tags');
	}

	if ($res === false || !is_array($res)) {
		$query = $db->query("SELECT name, howmuch FROM tags WHERE howmuch > 0 ORDER BY RAND() LIMIT 15");
		$tags_cache = array();

		while ($cache_data = $db->get_row($query)) {
			if (is_array($cache_data)) {
				$tags_cache[] = $cache_data;
			}
		}

		if (isset($memcached) && is_object($memcached)) {
			$memcached->set('tags', $tags_cache, 0, 24 * 60 * 60);
		}

		$res = $tags_cache;
	}

	foreach ($res as $row) {
		if (!is_array($row)) {
			continue;
		}

		$name = isset($row['name']) ? trim((string)$row['name']) : '';
		$howmuch = isset($row['howmuch']) ? (int)$row['howmuch'] : 0;

		if ($name !== '') {
			$arr[$name] = $howmuch;
		}
	}

	return $arr;
}

function cloud($small, $big, $colour = true)
{
	$tags = get_tags();

	if (empty($tags)) {
		$data = "Нет тэгов";
	} else {
		$minimum_count = min(array_values($tags));
		$maximum_count = max(array_values($tags));
		$spread = $maximum_count - $minimum_count;

		if ($spread == 0) {
			$spread = 1;
		}

		$data = '';
		$cloud = array();

		foreach ($tags as $tag => $count) {
			if (!empty($tag)) {
				$size = $small + ($count - $minimum_count) * ($big - $small) / $spread;
				$colours = array('#003EFF', '#0000FF', '#7EB6FF', '#0099CC', '#62B1F6');

				$cloud[] = "<a href=\"browse.php?search=";
				$cloud[] = urlencode($tag);
				$cloud[] = "&type=tags\" style=\"" . ($colour ? "color:" . $colours[mt_rand(0, 4)] . "; " : "") . "font-size:" . floor($size) . "px;\" rel=\"tag\" title=\"Содержится в " . (int)$count . " торрентах\">";
				$cloud[] = htmlentities($tag, ENT_QUOTES, 'UTF-8') . "(" . (int)$count . ")</a>\n";
			}
		}

		$data = join($cloud);
		unset($cloud);
	}

	return $data;
}

function flash_cloud($width, $height, $small, $big)
{
	$divname = 'tagcloud';
	$soname = 'settings';
	$movie = './public/swf/tagcloud.swf';
	$path = './public/js';

	$options = array();
	$options['bgcolor'] = 'FFFFFF';
	$options['trans'] = 'true';
	$options['tcolor'] = '888888';
	$options['tcolor2'] = '333333';
	$options['hicolor'] = '222222';
	$options['speed'] = '300';
	$options['distr'] = 'true';
	$options['mode'] = 'tags';

	ob_start();
	echo cloud($small, $big);
	$tags = urlencode(str_replace("&nbsp;", " ", ob_get_clean()));

	$flashtag = '';
	$flashtag .= '<script type="text/javascript" src="' . $path . '/swfobject.js"> </script>';
	$flashtag .= '<div id="' . $divname . '"><p style="display:none;">';
	$flashtag .= urldecode($tags);
	$flashtag .= '</p></div>';
	$flashtag .= '<script type="text/javascript">';
	$flashtag .= 'var rnumber = Math.floor(Math.random()*9999999);';
	$flashtag .= 'var ' . $soname . ' = new SWFObject("' . $movie . '?r="+rnumber, "tagcloudflash", "' . $width . '", "' . $height . '", "9", "#' . $options['bgcolor'] . '");';

	if ($options['trans'] == 'true') {
		$flashtag .= $soname . '.addParam("wmode", "transparent");';
	}

	$flashtag .= $soname . '.addParam("allowScriptAccess", "always");';
	$flashtag .= $soname . '.addVariable("tcolor", "0x' . $options['tcolor'] . '");';
	$flashtag .= $soname . '.addVariable("tcolor2", "0x' . ($options['tcolor2'] == "" ? $options['tcolor'] : $options['tcolor2']) . '");';
	$flashtag .= $soname . '.addVariable("hicolor", "0x' . ($options['hicolor'] == "" ? $options['tcolor'] : $options['hicolor']) . '");';
	$flashtag .= $soname . '.addVariable("tspeed", "' . $options['speed'] . '");';
	$flashtag .= $soname . '.addVariable("distr", "' . $options['distr'] . '");';
	$flashtag .= $soname . '.addVariable("mode", "' . $options['mode'] . '");';
	$flashtag .= $soname . '.addVariable("tagcloud", "' . urlencode('<tags>') . $tags . urlencode('</tags>') . '");';
	$flashtag .= $soname . '.write("' . $divname . '");';
	$flashtag .= '</script>';

	return $flashtag;
}

function simple_cloud($small, $big)
{
	$data = '<style>
        #tag_cloud a {padding: 3px; text-decoration: none; font-family: verdana; font-weight: normal;}
        #tag_cloud a:link {text-decoration: none; border: 1px solid transparent;}
        #tag_cloud a:visited {border: 1px solid transparent;}
        #tag_cloud a:hover {background: #ddd; border: 1px solid #bbb;}
        #tag_cloud a:active {background: #fff; border: 1px solid transparent;}
        #tag_cloud p {line-height: 28px; text-align: justify;}
        #tag_cloud {width:90%}
        </style>';
	$data .= '<div id="tag_cloud">';
	$data .= '<p>' . cloud($small, $big, true) . '</p>';
	$data .= '</div>';

	return $data;
}

// Вывод тегов
function get_tags_type()
{
	if (empty($_COOKIE['tags_module'])) {
		return simple_cloud(15, 20);
	} else {
		return flash_cloud('100%', '100', '30', '50');
	}
}

// Вывод тегов к релизу
function tags_echo($addtags)
{
	$tags = '';

	$addtags = trim((string)$addtags);

	if ($addtags === '') {
		return 'Нет тэгов';
	}

	$tag_list = explode(',', $addtags);
	$result = array();

	foreach ($tag_list as $tag) {
		$tag = trim($tag);

		if ($tag === '') {
			continue;
		}

		$result[] = "<a style=\"font-weight:normal;\" href=\"browse.php?search=" . urlencode($tag) . "&type=tags\">" . htmlspecialchars_uni($tag) . "</a>";
	}

	if (!empty($result)) {
		$tags = implode(', ', $result);
	}

	if ($tags === '') {
		$tags = 'Нет тэгов';
	}

	return $tags;
}

?>
