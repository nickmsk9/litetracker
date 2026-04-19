<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Управление блоками
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

if(!$PRIV['EDIT_PRIV']) {
	err('Ошибка' , 'У вас нет прав просматривать данную страницу');
}


//////////////////////////////////////////////
//Удаление блока
//////////////////////////////////////////////
if($_GET['act'] == 'del') {
	$bid = (int)$_GET['bid'];
	$sql = $db->query("SELECT * FROM orbital_blocks WHERE bid=".$bid);
	if(!$db->num_rows($sql) ) {
		err('Ошибка' , 'Данный блок не найден' , 1);
	}
	$arr = $db->get_row();

	//Пользователь согласился , удаляем
	if($_GET['take']) {
		$db->query("DELETE FROM orbital_blocks WHERE bid=".$bid);
		$memcache->delete('block_'.$arr['position'] , 0);
		header('Location:blocks.php?position='.$arr['position'].'&status=2');
		die();
	}

	//Выводим предупреждение
	head('Удаление блока');
	begin_frame('Удаление блока');
	msg('Внимание!' , 'Вы удаляете блок , т.е он не будет больше отображаться на сайте , так и в списке блоков<br><b>Это так ?</b> &nbsp
	<input type="button" value="Да , точно !" onCLick="window.location.href=\'blocks.php?act=del&bid='.$bid.'&take=1\'"> &nbsp
	<input type="button" value="Нет , назад" onClick="history.go(-1);"> ');

	end_frame();
	foot();

	die();
}
//////////////////////////////////////////////
//Добавление блока
//////////////////////////////////////////////
if($_GET['act'] == 'add') {

	//Редактирование
	if($_GET['bid']) {
		$bid = (int)$_GET['bid'];
		$sql = $db->query("SELECT * FROM orbital_blocks WHERE bid=".$bid);
		if(!$db->num_rows($sql) ) {
			err('Ошибка' , 'Данный блок не найден' , 1);
		}

		//Массив с данными
		$arr = $db->get_row();
	}

	if($_POST) {
		$update= array();

		//Название
		$title = $_POST['title'];
		$update[] = 'title="'.$db->safesql($title).'"';

		if(empty($title) ) {
			err('Ошибка' , 'Вы не ввели название' , 1);
		}

		if(strlen($title) > 60 ) {
			err('Ошибка' , 'Название превышает 60 символов' , 1);
		}



		//Файл
		$blockfile = $_POST['blockfile'];
		$update[] = 'blockfile="'.$db->safesql($blockfile).'"';

		if(!is_file('blocks/'.$blockfile) )  {
			err('Ошибка' , 'Выберите файл из списка' , 1);
		}

		//Позиция
		$position = $_POST['position'];
		$update[] = 'position="'.$db->safesql($position).'"';
		$array = array('l' ,'r' , 'c' , 'd');
		if(!in_array($position , $array) ) {
			err('Ошибка' , 'Данной позиции не существует' , 1);
		}

		//Активный
		$active = ($_POST['active'] ? 1 : 0);
		$update[] = 'active="'.$active.'"';

		//Тип
		$type = $_POST['type'];
		$update[] = 'type="'.$db->safesql($type).'"';
		$array = array('all' ,'guests' , 'users' , 'moderators' , 'administrators');
		if(!in_array($type , $array) ) {
			err('Ошибка' , 'Данного типа не существует' , 1);
		}

		//Зона видимости
		$which  = $_POST['which'];
		// die($which);
		if(empty($which) ) {
			$which = 'all';
		}
		$update[] = 'which="'.$db->safesql($which).'"';

		// die(implode(','  , $update));


		//Пишем в базу
		if(!$bid) {
			$weight = $db->super_query("SELECT weight AS c FROM orbital_blocks WHERE position = '".$position."' ORDER BY weight DESC LIMIT 1");
			if($weight['c']) {
				$update[] = 'weight='.$weight['c'];
			} else {
				$update[] = 'weight=1';
			}

			$db->query("INSERT INTO orbital_blocks SET ".implode(' , ' , $update));
		} else {
			$db->query("UPDATE orbital_blocks SET ".implode(' , ' , $update)." WHERE bid=".$bid);
		}

		//Удаляем кеш
		$memcache->delete('block_'.$position , 0);
		if($bid) {
			$memcache->delete('block_'.$arr['position'] , 0);
		}
		//Редирект
		header('Location: blocks.php?status=1&position='.$position.'');

	}


	$title = (!$bid ? 'Добавление блока' : 'Редактирование блока');
	head($title);


	begin_frame($title);
	?>
		<form action="blocks.php?act=add&bid=<?=$bid;?>" method="post">
		<table width="40%" align="center">
			<tr>
				<td width="10%"><b>Название:</b></td>
				<td><input type="text" name="title" value="<?=htmlspecialchars($arr['title']);?>" size="50"></td>
			</tr>

			<tr>
				<td width="10%"><b>Файл:</b></td>
				<td>
					<select name="blockfile">
					<option value="0">(Выберите)</option>
					<?
						$open = opendir('blocks');
						while ($file = readdir($open)) {
							if ($file != "." && $file != ".." && $file != '.htaccess') {
								echo '<option value="'.$file.'" '.($arr['blockfile'] == $file ? 'selected' : '').'>'.$file.'</option>';
							}
						}
						closedir($open);
					?>
					</select>
				</td>


			</tr>

			<tr>
				<td width="10%"><b>Позиция:</b></td>
				<td>
					<select name="position">
					<option value="">(Выберите)</option>
					<option value="l" <?=($arr['position'] == 'l' ? 'selected' : '' );?>>Левый</option>
					<option value="c" <?=($arr['position'] == 'c' ? 'selected' : '' );?>>Центральный (вверху)</option>
					<option value="d" <?=($arr['position'] == 'd' ? 'selected' : '' );?>>Центральный (вниз)</option>
					<option value="r" <?=($arr['position'] == 'r' ? 'selected' : '' );?>>Справа</option>
					</select>
				</td>
			</tr>

			<tr>
				<td width="10%"><b>Активен:</b></td>
				<td>
					<select name="active">
					<option value="1" <?=($arr['active'] == '1' ? 'selected' : '' );?>>Да</option>
					<option value="0" <?=($arr['active'] == '0' ? 'selected' : '' );?>>Нет</option>

					</select>
				</td>
			</tr>

			<tr>
				<td width="10%"><b>Тип:</b></td>
				<td>
					<select name="type">
					<option value="0">(Выберите)</option>
					<option value="all" <?=($arr['type'] == 'all' ? 'selected' : '' );?>>Всем</option>
					<option value="guests" <?=($arr['type'] == 'guests' ? 'selected' : '' );?>>Гостям</option>
					<option value="users" <?=($arr['type'] == 'users' ? 'selected' : '' );?>>Пользователям</option>
					<option value="moderators" <?=($arr['type'] == 'moderators' ? 'selected' : '' );?>>Модераторам</option>
					<option value="administrators" <?=($arr['type'] == 'administrators' ? 'selected' : '' );?>>Администраторам</option>
					</select>
				</td>
			</tr>

			<tr>
				<td width="10%"><b>Зона видимисти:</b></td>
				<td>
					<input type="text" name="which" value="<?=(!$bid ? 'all' : htmlspecialchars($arr['which']) );?>"><br>
					<small>Вводите имя файла ( без .php) , если хотите , чтобы он отображался там ( через запятую , к примеру , index,login,signup ). <b>all</b> - везде </small>
				</td>
			</tr>


			<tr>
				<td width="10%"></td>
				<td>
					<input type="submit" value="Выполнить">
				</td>
			</tr>


		</table>
		</form>
	<?
	end_frame();
	foot();
	die();
}

//////////////////////////////////////////////
//Вывод блоков
//////////////////////////////////////////////
//Определяем позицию
$position = (string)$_GET['position'];

//Запрос к базе
$sql = $db->query("SELECT *
				   FROM orbital_blocks
				   WHERE position='".$db->safesql($position)."'

				   ORDER BY weight ASC ");


head('Управление блоками');


switch($_GET['status']) {
		case '1' : msg('Успешно' , 'Задание выполнено'); break;
		case '2' : msg('Успешно' , 'Блок удален'); break;
	}


begin_frame('Управление блоками');

//Если блоки локально отключены
if(!$config['blocks_use'])  {
	msg('Внимание!' , 'Блочная система отключена локально ! Вы можете редактировать блоки , но никто их не увидит ! Включить блоки можно в system/config.php');
}


//Выводим блоки
?>

<!--Позиция блоков-->
<input type="button" value="Слева" onClick="window.location.href='blocks.php?position=l'">
<input type="button" value="По центру сверху"  onClick="window.location.href='blocks.php?position=c'">
<input type="button" value="По центру снизу"  onClick="window.location.href='blocks.php?position=d'">
<input type="button" value="Справа"  onClick="window.location.href='blocks.php?position=r'">
<div style="float:right">
<input type="button" value="Добавить блок" onClick="window.location.href='blocks.php?act=add'">

</div>
<br><br>
<?
if(!$db->num_rows($sql) ) {
	if(empty($position) ) {
		msg('Внимание'  , 'Выбирете позицию блока');
	} else {
		msg('Внимание' , 'Ниодного блока не найдено');
	}
} else {

?>


	<form action="blocks.php?act" method="post">
	<table width="100%">

	<tr>
	<td width="1%"><u>Номер</u></td>
	<td width="15%"><u>Название</u></td>
	<td width="5%"><u>Активность</u></td>
	<td><u>Видимость</u></td>
	<td><u>Где видим</u></td>
	<td><u>Перемещение</u></td>
	<td><u>Редактирование</u></td>

	</tr>


	<?
	while($arr = $db->get_row($sql) ) {

		?>
			<tr>
				<td>#<?=$arr['bid'];?>
				<td><b><?=htmlspecialchars($arr['title']);?></b></td>


				<td width="1">
					<?=($arr['active'] ? '<img src="public/images/ok.gif" title="Активный">' : '<img src="public/images/error.gif" title="Неактивный">');?>
				</td>




				<td>
					<?
						switch($arr['type']) {
							case 'all':
								echo 'Всем';
							break;

							case 'guests':
								echo 'Гостям';
							break;

							case 'users':
								echo 'Пользователям';
							break;

							case 'moderators':
								echo 'Модераторам';
							break;

							case 'administrators':
								echo 'Администраторам';
							break;

							default:
								echo 'Неизвестно';
							break;
						}
					?>

				</td>

				<td>
					<?
						if($arr['which'] == 'all')  {
							echo 'Везде';
						} elseif($arr['which'] != 'all' && !empty($arr['which']) ) {
							$which = explode(',' , $arr['which']);
							$resource = array();
							foreach($which AS $row) {
								$resource[] =  '<a href="'.$row.'.php" target="_blank">'.$row.'.php</a>';
							}

							echo implode(',' , $resource);
						} else {
							echo 'Неизвестно';
						}
					?>
				</td>

				<td>
					<?=($arr['weight'] != '1' ? '<img src="public/images/up.png" title="Поднять вверх">' : '');?>
					<img src="public/images/down.png" title="Опустить вниз">
				</td>

				<td>
					<a href="blocks.php?act=add&bid=<?=$arr['bid'];?>"><img src="public/images/clipboard__pencil.png" title="Редактировать блок"></a>
					<a href="blocks.php?act=del&bid=<?=$arr['bid'];?>"><img src="public/images/broom.png" title="Удалить блок"></a>
				</td>
			</tr>


		<?

	}

	?>
	</table>
	</form>


<?
}
end_frame();
foot();
?>
