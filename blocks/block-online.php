<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Кто он-лайн
===================================================================
*/

global $config , $language , $USER , $db , $memcache, $PRIV , $rewrite;

#----->Bots Array<-----#
$bots = array(
"Google <img src=\"public/images/google.png\" title=\"Google\">" => "Googlebot",
"Yandex <img src=\"public/images/yandex.png\" title=\"Yandex\">" => "Yandex",
"MSN Bot" => "msn",
"Yahoo <img src=\"public/images/yahoo.png\" title=\"Yandex\">" => "Yahoo",
"Rambler" => "Rambler",
);

///////////////////////////////////////////////////////////////
//Кто он-лайн
///////////////////////////////////////////////////////////////
if (false === ($online = $memcache->get('online')))
{
	$online = array();
	$online_who = array();
		
	
	$dt = gmtime() - (15 * 60);
    $dt = $db->safesql(get_date_time($dt));
    //end
		
	//Выполняем запрос
	$result = $db->query("SELECT DISTINCT sessions.user_id , sessions.user_agent , users.name , users.class  , users.sex
	FROM sessions
	LEFT JOIN users ON users.id = sessions.user_id  		
	WHERE sessions.last_access >= '".$dt."' ORDER BY users.class DESC
	LIMIT 100");
	while ($online_data = $db->get_row() )
	{
		$online_who[] = $online_data;
	}	
	
	$online['list'] = $online_who;		
			
	//Определяем сколько нас сегодня посетило
	$count = $db->query("SELECT * FROM sessions WHERE ADDDATE(last_access, INTERVAL 1 DAY) >= NOW()");
	$count = $db->num_rows();
	$online['count'] = $count;	
		
	$memcache->set('online', $online , 0, (2 * 60 ));
}	

$numUsers = 0; //Сколько пользователей
$numGuests = 0; //Сколько гостей
$numAll = $online['count']; //Всего за день
$arrayOnline  = array();
///////////////////////////////////////////////////////////////
//Вывод
///////////////////////////////////////////////////////////////
if(count($online['list']) ) {
	foreach ($online['list'] as $arr) 
	{
		//Определение количества пользователей
		if($arr['user_id'] != '-1')
		{
			$numUsers++;
			$userName = htmlspecialchars($arr['name']);
			$arrayOnline[] =  '<a href="'.profile_href($arr['user_id']).'" class="online">'.get_user_color($arr['class'], $userName).' '.($arr['sex'] ? '<img src="public/images/male.png" title="Мужской">' : '<img src="public/images/female.png" title="Женский">').'</a>';
		}
		else {
			$numGuests++;
			foreach($bots AS $name => $value) {	
				if (strstr($arr['user_agent'], $value)) { 
					$arrayOnline[] = $name;	
				}
			}
		}
	}
	
	$onlineList = implode(", " , $arrayOnline);
}else
	$onlineList =  'Нет активных пользователей...';


require 'templates/'.$config['template'].'/blocks/block.online.php';	
?>
