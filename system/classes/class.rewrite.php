<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Преобразование ссылок (ЧПУ)
===================================================================
*/

class rewrite {
	var $url = null; //URL 
	var $url_old = null; //Входной URL
	
	
	//Преобразуем в ЧПУ 
	function encode($url) {
		global $config;

		if (preg_match('~^profile\.php\?(.*)$~i', (string) $url, $matches)) {
			$params = array();
			parse_str($matches[1], $params);

			$userId = (int) ($params['id'] ?? 0);
			if ($userId > 0) {
				$view = trim((string) ($params['view'] ?? 'profile'));
				unset($params['id'], $params['view']);
				return profile_href($userId, $view, $params);
			}
		}

		//Если не используем мод , не преобразуем ссылку
		if(!$config['rewrite']) {
			return $url;
		}
		$this->url_old = $url; //Устанавливаем входной URL
		$array = array('.php' => ''); //Массив для преобразования
		//  , '?' => '/' , '&' => '/' , '=' => '-'
		
		foreach($array AS $param => $value) {
			$url = str_replace($param , $value , $url);
		}
		return $url;
	}
	
	
}
?>
