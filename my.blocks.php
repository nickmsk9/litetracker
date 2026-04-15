<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Мои сообщения
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверяем пользователя
is_login();

if(!$config['blocks_use']) { 
	err('Ошибка'  , 'На нашем сайте не используются блоки');
}


///////////////////////////////////////////////////
//Обработка
///////////////////////////////////////////////////
if($_POST) {
	$use = $_POST['use'];
	// print_r($use);
	// die();
	//хак от wennet'a
	$domain = $_SERVER['HTTP_HOST'];
	if ( strtolower( substr($domain, 0, 4) ) == 'www.' )
		$domain = substr($domain, 4);	// Fix the domain to accept domains with and without 'www.'. 
	if ( substr($domain, 0, 1) != '.' )
		$domain = '.'.$domain;	// Add the dot prefix to ensure compatibility with subdomains

	//Перебираем в цикле
	foreach($use AS  $id => $value) {
		// die($value);
		// $value = ($value == 0 ? 0 : 1);
		//Перезаписываем cookies
		setcookie('block_'.$id, $value, 0x7fffffff, "/" , $domain); 
		$_COOKIE['block_'.$id] = $value;
	}
	
	header('Location:my.blocks.php');
	die();
}	



///////////////////////////////////////////////////
//Вывод
///////////////////////////////////////////////////

head('Мои блоки');

begin_frame('Мои блоки');
msg('Данная функция позволяет вам настроить наш трекер по своему вкусу' , 'Выберите какие вы хотите видеть , а какие нет');

$sql = $db->query("SELECT * FROM orbital_blocks WHERE active = '1' ORDER BY position DESC");

if(!$db->num_rows($sql)) {
	msg('Извините' , 'Но блоков на трекере не найдено');
}	else { 
	?>
	<form action="my.blocks.php" method="post">
	<table width="50%">
	<tr>
		<td></td>
		<td width="20%"><u>Название</u></td>
		<td><u>Где выводится</u></td>
	</tr>
	<?
	while($arr = $db->get_row($sql)  ) {
		if($arr['type'] == 'guests' or ($arr['type'] == 'moderators' && $PRIV['block_moderators']) or ($arr['type'] == 'administrators' && $PRIV['block_administrators']) ) {
			continue;
		}	
		
		?>
		<tr>
			<td width="1%">
			<select name="use[<?=$arr['bid'];?>]">
				<option value="0" <?=($_COOKIE['block_'.$arr['bid']] == 0 ? 'selected' : '');?>>Видим</option>
				<option value="1" <?=($_COOKIE['block_'.$arr['bid']] == 1 ? 'selected' : '');?>>Не видим</option>
			</select>
			</td>
			<td><?=htmlspecialchars($arr['title']);?></td>
			<td>
				<?
				switch($arr['position']) {
					case 'l':
						echo 'Слева';
					break;
					
					case 'r':
						echo 'Справа';
					break;
					
					case 'c':
						echo 'По центру вверху';
					break;
					
					case 'd':
						echo 'По центру снизу';
					break;
					
					default:
						echo 'Неизвестно';
					break;
				}
				?>
			</td>
		</tr>
		<?
	}
	?>
	</table>
	<input type="submit" value="Редактировать">
	</form>
	<?
}

end_frame();
foot();
?>