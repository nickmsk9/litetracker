<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Управление опросами
===================================================================
*/
//Подключаем главный системный файл
require 'system/init.php';


//Проверка авторизации
is_login();


//Проверяем права
if(!$PRIV['polls_moderate']) {
	err($language['default_1'] , 'Вам запрещено сюда' , 1);
}


/////////////////////////////////////////////////////////////
//Удаление опроса
/////////////////////////////////////////////////////////////
if($_GET['act'] == 'del') {
	$id = (int)$_GET['id'];
	$sql = $db->query("SELECT * FROM polls WHERE id=".$id);
	if(!$db->num_rows($sql) ) {
		err("Ошибка" , "Данного опроса не существует!", 1);
	}	
	
	//Удаляем опрос
	$db->query("DELETE FROM polls WHERE id=".$id);
	//Удаляем варианты ответа
	$db->query("DELETE FROM polls_questions WHERE id_poll=".$id);
	//Удаляем ответы
	$db->query("DELETE FROM polls_voting WHERE id_poll=".$id);
	
	//Перенаправление
	header("Location:polls.php?status=2");
	die();
}
	
/////////////////////////////////////////////////////////////
//Добавление / Редактирование опроса
/////////////////////////////////////////////////////////////
if($_GET['act'] == 'app') {
	//Если редактирование
	if($_GET['id']) {
		$id = (int)$_GET['id'];
		$sql = $db->query("SELECT * FROM polls WHERE id=".$id);
		if(!$db->num_rows($sql) ) {
			err("Ошибка" , "Данного опроса не существует!", 1);
		}	
		$arr = $db->get_row($sql);
	}
	
	//Обработка
	if($_POST)  {
		$insert = array();
		//Название опроса
		$subject = trim($_POST['subject']);
		$insert[] = 'subject="'.$db->safesql($subject).'"';
		if(empty($subject) ) {
			err('Ошибка' , 'Введите имя опроса' , 1);
		}
		
		//Сортировка
		$sort = ((bool)$_POST['sort'] ? 1 : 0 );
		
		$insert[] = 'sort='.$sort.'';
		
		//Варианты ответа
		$questions = (array)$_POST['questions'];
		if(!isset($questions) ) {
			err("Ошибка" , "Вы не ввели ниодного варианта ответа");
		}	
		
		
		//Добавляем/Обновляем сам опрос
		if($_GET['id']) {
			$db->query("UPDATE polls SET ".implode(' , ' , $insert)." WHERE id=".$id);
		}	else {
			$insert[] = 'date = NOW()';
			$db->query("INSERT INTO polls  SET ".implode(' , ' , $insert));
			$id = $db->insert_id();
		}
		
		//Удаляем старые варианты
		if($_GET['id'])  {
			$db->query("DELETE FROM polls_questions WHERE id_poll = ".$id);
		}
		
		//Перебираем в цикле
		foreach($questions AS $subject) {
			$subject = trim($subject);
			if(!empty($subject) ) {
				$db->query("INSERT INTO polls_questions (id_poll , subject) VALUES (".$id." , '".$db->safesql($subject)."')");
			}
		}
		
		
		
		//Переадресация
		header("Location:polls.php?status=1");
		die();
	}
	
	
	//Для вывода вариантов ответа
	$questions = 15;
	if($_GET['id']) { 
		$sql = $db->query("SELECT * FROM polls_questions WHERE id_poll=".$id);
		$i = 1;
		while($row = $db->get_row($sql) ) {
			$questions_form .= '<tr><td>Вариант #'.$i.'</td><td><input type="text" name="questions[]" value="'.htmlspecialchars($row['subject']).'" size="50%">';
			$i++;
		}
		
		//Выводим не достающие поля
		for( ; $i <= $questions  ; $i++) {
			$questions_form .= '<tr><td>Вариант #'.$i.'</td><td><input type="text" name="questions[]" value="" size="50%">';
		}
	} else { 
		for($i = 1  ; $i <= $questions ; $i++) {
			$questions_form .= '<tr><td>Вариант #'.$i.'</td><td><input type="text" name="questions[]" value="" size="50%">';
		}
	}
	//Вывод формы
	$title = ($_GET['id']? 'Редактирование опроса' : 'Добавление опроса');
	head($title);
	begin_frame($title);
	?>
		<form action="polls.php?act=app&id=<?=$id;?>" method="post">
			<table class="tableform">
			<tr><td><b>Название опроса:</b></td><td><input type="text" name="subject" value="<?=htmlspecialchars($arr['subject']);?>" size="50%"></td></tr>
			<tr><td><b>Сортировка:</b></td><td><input type="checkbox" name="sort" value="1" <?=($arr['sort'] ? 'checked' : '');?> > Да</td></tr>
			<tr><td colspan="2"><b><center>Варианты ответа</center></b></td></tr>
			<?=$questions_form;?>
			<tr><td colspan="2"><input type="submit" value="Выполнить"></td></tr>
			</table>
		</form>
	<?
	end_frame();
	foot();
	die();
}

/////////////////////////////////////////////////////////////
//Вывод опросов
/////////////////////////////////////////////////////////////
head('Опросы');

//Статусы
switch($_GET['status']) { 
	case '1' : msg('Успешно' , 'Действие успешно выполнено'); break;
	case '1' : msg('Успешно' , 'Опрос успешно удален'); break;
}

//Функции
begin_frame();
echo '<input type="button" value="Добавить опрос" onClick="window.location.href=\'polls.php?act=app\'">';
end_frame();


//Запрос к базе данных
$sql = $db->query("SELECT * FROM polls ORDER BY date DESC");
if(!$db->num_rows($sql) ) {
	msg('Ошибка' , 'Ниодного опроса не найдено');
} else  {
	begin_frame('Опросы');
	echo '<table class="tableform">
	<tr>
		<td><b>Название</b></td>
		<td><b>Создан</b></td>
		<td><b>Сортировка</b></td>
		<td><b>Действия</b></td>
		
	</tr>';
	while($arr = $db->get_row($sql) ) {
		echo '<tr>
			<td><A href="polls.php?act=view&id='.$arr['id'].'">'.htmlspecialchars($arr['subject']).'</a></td>
			<td>'.convent_date($arr['date']).'</td>
			<td>'.($arr['sort'] ? 'Сортируется' : 'Не сортируется').'</td>
			<td>
				<input type="button" value="Ред." onClick="window.location.href=\'polls.php?act=app&id='.$arr['id'].'\'">
			    <input type="button" value="Удал." onClick="window.location.href=\'polls.php?act=del&id='.$arr['id'].'\'">
			</td>
			
		</tr>';
	}
	echo '</table>';
	end_frame();
}
foot();
?>