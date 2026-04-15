<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Сессии
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверка авторизации
is_login();

//Только Администраторам , Модераторам
if(!$PRIV['sessions_view']) {
	err('Ошибка' , 'Вам запрещено просматривать сессии' , 1);
}


//Принудительная очистка
if($_GET['clean'] && $PRIV['sessions_clear']) {
	//Удаляем все записи
	$db->query("DELETE FROM sessions");
}

head('Сессии' , true);

begin_frame('Сессии');



echo ($PRIV['sessions_clear'] ? '<input type="button" value="Очистить" onClick="window.location.href=\'sessions.php?clean=1\'">' : '');



//Постраничная навигация
$count  = $db->super_query("SELECT COUNT(*) AS c FROM sessions");
$count = $count['c'];
list($pagertop, $pagerbottom, $limit) = pager('40' , $count, "sessions.php?");

//Выводим все записи
$sql  = $db->query("SELECT  s.* , u.name , u.class 
				FROM sessions AS s
				LEFT JOIN users AS u ON u.id = s.user_id
				ORDER BY s.last_access DESC
				".$limit."");
if($db->num_rows($sql)) {
	echo $pagertop;
	?>
	<link href="public/css/torrenttable.css" rel="StyleSheet" type="text/css">
	<table width="100%" cellpadding="0" class="tt">
	<tr class="header">
	<td><b>Пользователь</b></td><td><b>Посл. посещение</b></td><td><b>IP</b></td>  <td><b>Агент</b></td><td><b>Просматривает</b></td>
	</tr>
	<?
	while($arr = $db->get_row($sql) ) {
		echo '<tr>';
		
		echo '<td>';
		if($arr['user_id'] == '-1') {
			echo 'Гость';
		}else {
			echo '<a href="profile.php?id='.$arr['user_id'].'">'.get_user_color($arr['class'] , $arr['name']).'</a>';
		}
		echo '</td>';
		
		echo '<td>'.convent_date($arr['last_access']).'</td>';
		echo '<td><a href="ip.util.php?ip='.long2ip($arr['ip']).'">'.long2ip($arr['ip']).'</a></td>';
		echo '<td>'.htmlspecialchars($arr['user_agent']).'</td>';
		echo '<td>'.htmlspecialchars($arr['php_self']).'</td>';
		
		
		echo '</tr>';
	}
	?>
	</table>
	<?
	
	echo $pagertop;
} else
	msg('Ошибка' , 'Сессий не найдено');
end_frame();
foot();