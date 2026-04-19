<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Массовая рассылка
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверка авторизации
is_login();

//Только Администраторам , Модераторам
if(!$PRIV['messages']) {
	err($language['default_1'] , 'Вам запрещено рассылать сообщения', 1);
}


//Обработка
if($_POST) {
	//Тема
	$name = trim($_POST['name']);
	if(empty($name) ) {
		err('Ошибка' , 'Введите тему сообщения' , 1);
	}

	//Сообщение
	$text =  trim($_POST['text']);
	if(empty($text) ) {
		err('Ошибка' , 'Вы не ввели текст сообщения' , 1);
	}

	$filter = $_POST['filter']; //Фильтрование
	$filter_arr = array();
	foreach($filter As $arr) {
		$filter_arr[] = 'class='.(int)$arr;
	}

	//Проверяем классы
	$db->query("SELECT id FROM users ".($filter_arr ? 'WHERE '.implode(' OR ' , $filter_arr) : '' ) );
	if(!$db->num_rows() ) {
		err('Ошибка' , 'Пользователей в данной группе не найдены' , 1);
	}

	$user_arr = array();
	while($row = $db->get_row() )
		$user_arr[] = $row['id'];



	//Системное
	$system = ($_POST['system'] ? '0' : $USER['id']);

	//Отправляем сообщение
	foreach($user_arr As $arr){
		send_msg($name , $text , $arr , $system);
	}
	header("Location:messages.php?status=1");
	die();
}


head('Массовая рассылка');

if($_GET['status'] == '1') {
	msg('Успешно' , 'Сообщения успешны разосланы');
}

begin_frame('Массовая рассылка');
?>
<form action="messages.php" method="post">

	<table width="70%" cellspacing="7" cellpadding="0" border="0" align="center">
	<tbody>

		<tr>
		<td class="ta_r">
		 <span class="grey">Тема:</span>
		</td>
		<td style="padding: 0px;">
			<input type="text" name="name" value="<?=htmlspecialchars($_POST['name']);?>">
		</td><td>
	   </td>
	   </tr>

	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey">Сообщение:</span>
		</td>
		<td style="padding: 0px;">
		 <textarea style="width:100%" name="text"><?=htmlspecialchars($_POST['text']);?></textarea>
		</td><td>
	   </td></tr>


	    <tr>
		<td class="ta_r" valign="top">
		 <span class="grey">Фильтр:</span>
		</td>
		<td style="padding: 0px;">
		 	<select name="filter[]" multiple="multiple">
			<?
			$classes = get_classes_list();
			foreach($classes AS $class) {
				echo '<option value="'.$class['id'].'">'.htmlspecialchars($class['NAME']).'</option>';
			}
			?>
			</select>
			<br><small>Вы можете выбрать, кому будут отправляться сообщения. Если ничего не выбрано , то сообщения будут отправлены всем!</small>
		</td><td>
	   </td></tr>

	  <tr>
		<td class="ta_r" valign="top">
		 <span class="grey">Системное:</span>
		</td>
		<td style="padding: 0px;">
		<input type="checkbox" value="1" name="system">&nbsp Данное сообщение будет являться системным
		</td><td>
	   </td></tr>

	    <tr>
		<td class="ta_r">
		 <span class="grey"></span>
		</td>
		<td style="padding: 0px;">
		 <input type="submit" value="Разослать">
		</td><td>
	   </td></tr>



	</tbody>
	</table>
	</form>
<?
end_frame();
foot();
