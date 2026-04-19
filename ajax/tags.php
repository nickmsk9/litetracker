<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Меняем тип тегов
===================================================================
*/

//Ajax
if($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {

	//Подключаем главный системный файл
	require $_SERVER['DOCUMENT_ROOT'].'/system/init.php';
	header("Content-Type: text/html; charset=".$language['charset']."");

	//Определяем тип
	$check_value = $_COOKIE['tags_module'];
	if($check_value)
		$value = false;
	else
		$value = true;

	//хак от wennet'a
	$domain = $_SERVER['HTTP_HOST'];
	if ( strtolower( substr($domain, 0, 4) ) == 'www.' )
		$domain = substr($domain, 4);	// Fix the domain to accept domains with and without 'www.'.
	if ( substr($domain, 0, 1) != '.' )
		$domain = '.'.$domain;	// Add the dot prefix to ensure compatibility with subdomains

	//Перезаписываем cookies
	setcookie('tags_module', $value, 0x7fffffff, "/" , $domain);
	$_COOKIE['tags_module'] = $value;
	echo get_tags_type();
}
?>
