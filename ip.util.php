<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: IP - утилиты
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверка авторизации
is_login();

//Только Администраторам , Модераторам
if(!$PRIV['ip_util']) {
	err('Ошибка' , 'Доступ закрыт' , 1);
}




/////////////////////////////////////////////////////////////////////////////////////////////////
//Общее
/////////////////////////////////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'bans_ip') {

	//Постраничная навигация
	$db->query("SELECT * FROM bans" , 1);

	$count = $db->num_rows();
	list($pagertop, $pagerbottom, $limit) = pager('10', $count, 'ip.util.php?'.(count($get) ? implode('&' ,$get).'&' : '') );

	$sql = $db->query("SELECT b.* , u.class AS class_user , u.name AS user_name FROM bans  AS b
				LEFT JOIN users AS u ON u.id = b.id_user
				ORDER BY b.date DESC
				".$limit."");


	if(!$db->num_rows($sql)) {
		err('Забаненных IP не найдено' , '<a href="ip.util.php?act=banned_ip">Заблокировать IP</a>' , 1);
	}

	head('Заблокированные IP' , true);

	if($_GET['status'] == '1') {
		msg('IP успешно заблокирован');
	}elseif($_GET['status'] == '2'){
		msg('IP успешно разблокирован');
	}




	begin_frame('Заблокированные IP');
	echo $pagertop;
	?>
	<br>
	<table width="100%" cellpadding="0" class="tt">
	<tr class="header">
	<td width="20%"><b>Первичный IP</b></td><td><b>Вторичный IP</b></td><td><b>Забанен</b></td><td><b>Пользователем</b></td><td><b>Комментарий</b></td> <td><b>Действия</b></td>
	</tr>
	<?
	while($arr = $db->get_row($sql) ) {
		echo '<tr>';

		echo '<td><a href="ip.util.php?ip='.long2ip($arr['first']).'">'.long2ip($arr['first']).'</a></td>';
		echo '<td><a href="ip.util.php?ip='.long2ip($arr['last']).'">'.long2ip($arr['last']).'</a></td>';

		echo '<td>'.convent_date($arr['date']).'</td>';
		echo '<td><A href="'.profile_href($arr['id_user']).'">'.get_user_color($arr['class_user'] , $arr['user_name']).'</a></td>';
		echo '<td>'.(empty($arr['text']) ? '<i>Без комментария...</i>' : htmlspecialchars($arr['text']) ).'</td>';
		echo '<td><input type="button" value="Разблокировать IP" onClick="window.location.href=\'ip.util.php?id='.$arr['id'].'&act=unlock_ip\'">
		</td>';

		echo '</tr>';
	}
	?>
	</table>
	<?

	echo $pagertop;
	end_frame();


	foot();
	die();
}

/////////////////////////////////////////////////////////////////////////////////////////////////
//Разблокировать IP
/////////////////////////////////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'unlock_ip' && $_GET['id']) {
	$id = (int)$_GET['id'];
	$db->query("SELECT * FROM bans WHERE id=".$id."");
	if(!$db->num_rows() ) {
		err('Ошибка' , 'Данной записи не существует' ,1);
	}
	//Удаляем запись
	$db->query("DELETE FROM bans WHERE id=".$id."");
	header('Location:ip.util.php?act=bans_ip&status=2');
	die();

}
/////////////////////////////////////////////////////////////////////////////////////////////////
//Заблокировать IP
/////////////////////////////////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'banned_ip') {

	//Обработка данных
	if($_POST) {
		//Первичный IP
		$ip_1 = trim($_POST['ip_1']);
		if (!validip($ip_1)) {
			err('Ошибка' , 'Первичный IP введен не корректно' , 1);
		}
		//Вторичный IP
		$ip_2 = trim($_POST['ip_2']);
		if(empty($ip_2) ) {
			$ip_2 = $ip_1;
		} else {
			if (!validip($ip_2)) {
				err('Ошибка' , 'Вторичный IP введен не корректно' , 1);
			}
		}
		//Комментарий
		$text = trim($_POST['text']);

		$db->query("INSERT INTO bans (first , last , date , id_user , text) VALUES (".ip2long_db($ip_1)." , ".ip2long_db($ip_2)." , NOW() , ".$USER['id']." , '".$text."')");
		header('Location:ip.util.php?act=bans_ip&status=1');
		die();
	}

	head('Заблокировать IP');
	begin_frame('Заблокировать IP');

	?>
	<form  action="ip.util.php?act=banned_ip"  id="loginPage" method="post">
	<table width="80%" cellspacing="7" cellpadding="0" border="0" align="center">
	   <tbody><tr>
		<td class="ta_r">
		 <span class="grey">Первый IP:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" style="margin: 0px;" size="25"  name="ip_1" class="inputText" value="">
		</td><td>
	   </td></tr>

	   <tr>
		<td class="ta_r">
		 <span class="grey">Второй IP:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" style="margin: 0px;" size="25"  name="ip_2" class="inputText" value="">
		 <br>
		 <small>Можно не вводить. Тогда будет забанен только первичный IP</small>
		</td><td>
	   </td></tr>

	   <tr>
		<td class="ta_r">
		 <span class="grey">Комментарий:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" style="margin: 0px;" size="25"  name="text" class="inputText" value="">
		</td>
	   </tr>





	   <tr>
		<td>
		 &nbsp;
		</td>
		<td>
	<div style="height: 20px; margin: 5px 0px;">
		<input type="submit" value="Заблокировать IP" >
	</div>

		</td>
	   </tr>


	  </tbody></table>

	  </form>


	<?
	end_frame();
	foot();

	die();
}



/////////////////////////////////////////////////////////////////////////////////////////////////
//Бан аккаунта
/////////////////////////////////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'bans_account') {

	//Постраничная навигация
	$count = $db->query("SELECT u.*
				FROM users  AS u
				LEFT JOIN priv AS p ON p.id = u.class
				WHERE u.banned='1' AND p.ip_util='0' " , 1);

	$count = $db->num_rows($count);
	list($pagertop, $pagerbottom, $limit) = pager('10', $count, 'ip.util.php?act=bans_account&' );

	$sql  = $db->query("SELECT u.*
				FROM users  AS u
				LEFT JOIN priv AS p ON p.id = u.class
				WHERE u.banned='1' AND p.ip_util='0'
				".$limit."");
	if(!$db->num_rows($sql) ) {
		err('Ошибка' , 'Забаненных аккаунтов не найдено' , 1);
	}


	head('Забаненные аккаунты' , true);
	begin_frame('Забаненные аккаунты');

	echo $pagertop;
	?>
	<br>
	<table width="100%" cellpadding="0" class="tt">
	<tr class="header">
	<td><b>Пользователь</b></td>
	<td width="20%"><b>Рейтинг</b></td>
	<td><b>Дата регистраци</b></td>
	<td><b>E-mail</b></td>
	<td><b>Последняя активность</b></td>
	<td><b>IP</b></td>
	<td><b>Действия</b></td>
	</tr>
	<?
	while($arr = $db->get_row($sql) ) {
		echo '<tr>';

		echo '<td><a href="'.profile_href($arr['id']).'">'.get_user_color($arr['class'] , $arr['name']).'</a></td>';
		echo '<td>'.get_ratio($arr['uploaded'] , $arr['downloaded']).'%</td>';
		echo '<td>'.convent_date($arr['added']).'</td>';
		echo '<td>'.htmlspecialchars($arr['email']).'</td>';
		echo '<td>'.convent_date($arr['last_access']).'</td>';
		echo '<td>'.long2ip($arr['ip']).'</td>';
		echo '<td><input type="button" value="Разбанить аккаунт" onClick="window.location.href=\'ip.util.php?id='.$arr['id'].'&act=banned_account\'">
		<br><br>
		</td>';

		echo '</tr>';
	}
	?>
	</table>
	<?

	echo $pagertop;

	end_frame();
	foot();

	die();
}




/////////////////////////////////////////////////////////////////////////////////////////////////
//Бан аккаунта
/////////////////////////////////////////////////////////////////////////////////////////////////
if($_GET['act'] == 'banned_account' && $_GET['id']) {
	$id = (int)$_GET['id'];
	$db->query("SELECT u.*
				FROM users  AS u
				LEFT JOIN priv AS p ON p.id = u.class
				WHERE u.id=".$id." AND p.ip_util='0'
				");
	if(!$db->num_rows() ) {
		err('Ошибка' , 'Данного пользователя не существует или данный пользователь из администрации' , 1);
	}
	$arr = $db->get_row();


	//Баним аккаунт
	if($arr['banned'] == 0) {
		$db->query("UPDATE users SET banned='1' WHERE id=".$id);
			$memcached->delete('user_'.$id);
			err('Успешно' , 'Аккаунт забанен' , 1 , 'success');
	} else {
	//Убираем бан
		$db->query("UPDATE users SET banned='0' WHERE id=".$id);
		$memcached->delete('user_'.$id);
		err('Успешно' , 'Аккаунт разбанен' , 1 , 'success');
	}

	die();
}




/////////////////////////////////////////////////////////////////////////////////////////////////
//Общее
/////////////////////////////////////////////////////////////////////////////////////////////////

head('IP утилиты' , true);

begin_frame('Дополнительные функции');
?>
<input type="button" value="Заблокировать IP" onCLick="window.location.href='ip.util.php?act=banned_ip'">
<input type="button" value="Заблокированные IP" onCLick="window.location.href='ip.util.php?act=bans_ip'">
<input type="button" value="Заблокированные аккаунты" onCLick="window.location.href='ip.util.php?act=bans_account'">
<?
end_frame();

begin_frame('Поиск');
?>
<form action="ip.util.php" method="GET">
<table width="95%" align="center">
	<tr>
	<td>
	<input type="text" name="ip" size="50%" class="search"   autocomplete="off" value="<?=htmlspecialchars((string)$_GET['ip']);?>">
	<input type="submit" value="Поиск" class="search">
	</td>
	</tr>


	<tr>
	<td>
	<small>IP можно ввести неполностью. К примеру 127.0.*.*</small>
	</td>
	</tr>


</table>
</form>
<?
end_frame();

if($_GET['ip']) {

	$where = array();
	$get = array();

	//Разбираем IP
	$ip = trim($_GET['ip']);

	if(!empty($ip)) {
		$ip = explode('.' , $ip);

		//Первый IP
		$first_ip = $ip;
		foreach($first_ip AS $i => $exp_ip) {
			if($exp_ip == '*') $first_ip[$i] = '0';
		}
		$first_ip = ip2long_db(implode('.' ,$first_ip) );


		//Второй IP
		$last_ip = $ip;
		foreach($last_ip AS $i => $exp_ip) {
			if($exp_ip == '*') $last_ip[$i] = '255';
		}
		$last_ip = ip2long_db(implode('.' ,$last_ip));




		$where[]  = "('$last_ip' >= ip AND '$first_ip' <= ip)";
		$get[] = 'ip='.$ip;
	}


	//Постраничная навигация
	$db->query("SELECT * FROM users ".(count($where) ? 'WHERE '.implode('AND ' , $where) : '')."" , 1);

	$count = $db->num_rows();
	list($pagertop, $pagerbottom, $limit) = pager('10', $count, 'ip.util.php?'.(count($get) ? implode('&' ,$get).'&' : '') );

	$db->query("SELECT * FROM users ".(count($where) ? 'WHERE '.implode('AND ' , $where) : '')." ".$limit."");


	if(!$db->num_rows()) {
		msg('Ничего не найдено');
	}else {
		begin_frame('Результат');

		echo $pagertop;
		?>
		<link href="public/css/torrenttable.css" rel="StyleSheet" type="text/css">
		<table width="100%" cellpadding="0" class="tt">
		<tr class="header">
		<td><b>Пользователь</b></td>
		<td width="20%"><b>Рейтинг</b></td><td><b>Дата регистраци</b></td><td><b>E-mail</b></td>
		<td><b>Последняя активность</b></td>
		<td><b>IP</b></td>
		<td><b>Действия</b></td>
		</tr>
		<?
		while($arr = $db->get_row() ) {
			echo '<tr>';

			echo '<td><a href="'.profile_href($arr['id']).'">'.get_user_color($arr['class'] , $arr['name']).'</a></td>';
			echo '<td>'.get_ratio($arr['uploaded'] , $arr['downloaded']).'%</td>';
			echo '<td>'.convent_date($arr['added']).'</td>';
			echo '<td>'.htmlspecialchars($arr['email']).'</td>';
			echo '<td>'.convent_date($arr['last_access']).'</td>';
			echo '<td>'.long2ip($arr['ip']).'</td>';
			echo '<td><input type="button" value="'.(!$arr['banned'] ? 'Забанить аккаунт' : 'Разбанить аккаунт' ).'" onClick="window.location.href=\'ip.util.php?id='.$arr['id'].'&act=banned_account\'">

			</td>';

			echo '</tr>';
		}
		?>
		</table>
		<?

		echo $pagertop;

		end_frame();
	}
}
foot();
