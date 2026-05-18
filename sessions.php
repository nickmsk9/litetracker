<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
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
if($_POST && !empty($_POST['clean']) && $PRIV['sessions_clear']) {
	if (!lt_csrf_validate('sessions_clean')) {
		err('Ошибка', 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

	//Удаляем все записи
	$db->query("DELETE FROM sessions");
	header('Location:sessions.php');
	die();
}

head('Сессии' , true);

begin_frame('Сессии');



if ($PRIV['sessions_clear']) {
	echo '<form action="sessions.php" method="post" class="inline-action-form">'.lt_csrf_input('sessions_clean').'<input type="hidden" name="clean" value="1"><button type="submit">Очистить</button></form>';
}



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
	<?php
	while($arr = $db->get_row($sql) ) {
		echo '<tr>';

		echo '<td>';
		if($arr['user_id'] == '-1') {
			echo 'Гость';
		}else {
			echo '<a href="'.profile_href($arr['user_id']).'">'.get_user_color($arr['class'] , $arr['name']).'</a>';
		}
		echo '</td>';

		echo '<td>'.convent_date($arr['last_access']).'</td>';
		echo '<td><a href="ip.util.php?ip='.long2ip($arr['ip']).'">'.long2ip($arr['ip']).'</a></td>';
		echo '<td>'.htmlspecialchars((string) ($arr['user_agent'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>';
		echo '<td>'.htmlspecialchars((string) ($arr['php_self'] ?? ''), ENT_QUOTES, 'UTF-8').'</td>';


		echo '</tr>';
	}
	?>
	</table>
	<?php

	echo $pagertop;
} else
	msg('Ошибка' , 'Сессий не найдено');
end_frame();
foot();
