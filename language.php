<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Смена языка
===================================================================
*/


//Подключаем главный системный файл
require 'system/init.php';

$language = $_POST['language'];
if(!is_language($language)) {
	err($language['default_1'] , $language['default_6']);
}

if($config['cookies_mode']) {
	$subnet = explode('.', getip());
	$subnet[2] = $subnet[3] = 0;
	$subnet = implode('.', $subnet); // 255.255.0.0

	// хак от wennet'a
	$domain = $_SERVER['HTTP_HOST'];
	if ( strtolower( substr($domain, 0, 4) ) == 'www.' )
		$domain = substr($domain, 4);	// Fix the domain to accept domains with and without 'www.'. 
	if ( substr($domain, 0, 1) != '.' )
		$domain = '.'.$domain;	// Add the dot prefix to ensure compatibility with subdomains
}
setcookie("language", $language, 0x7fffffff , '/' ,  $domain , false , true);

header('Location:index.php');
die();
?>