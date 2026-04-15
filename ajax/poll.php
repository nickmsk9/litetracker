<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Опросы
===================================================================
*/
 
//Ajax 
if($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
	//Подключаем главный системный файл
	require '../system/init.php';
	global $USER;
	
	//Гостям запрещаем
	if(!$USER) {
		die();
	}
	
	/////////////////////////////////////////////////////
	//Вывод опроса
	/////////////////////////////////////////////////////
	if($_POST['do'] == "load") {
		//Запрос к базе данных 
		$sql = $db->query("SELECT * FROM polls ORDER BY date DESC LIMIT 1");
		if(!$db->num_rows($sql) ) {
			die('Опросов не найдено');
		} else {
			$arr = $db->get_row($sql);
			
			//Запрос к вариантам ответа
			$questions = $db->query("SELECT * FROM polls_questions WHERE id_poll=".$arr['id']);
			
			//Проверка , голосовал ли пользователь
			$voting = $db->super_query("SELECT COUNT(*) AS c FROM polls_voting WHERE id_poll=".$arr['id']." AND id_user=".$USER['id']);
			
			//Заголовок
			echo '<div id="poll_title">'.htmlspecialchars($arr['subject']).'</div>';
		
			//Выбор вариантов ответов
			if(!$voting['c']) {
				echo '<form action="javascript:void();">';
				echo '<table>';
				while($question = $db->get_row($questions)) {
					echo '<tr><td><input type="radio" onclick="addvote('.$question['id'].')" name="question" value="'.$question['id'].'"> '.htmlspecialchars($question['subject']).'</td></tr>';
				}
				
				//Пустой голос , увидеть результаты
				// echo '<tr><td><input type="radio" onclick="addvote(0)" name="question" value="0"> Пустой голос , я хочу увидеть результаты!</td></tr>';
				
				
				//Кнопка отправки
				echo '<tr><td><input type="button" value="Голосовать" style="display:none;" id="poll_button" onclick="vote();"/></td></tr>';
				echo '</table>';
				
				//Невидимая кнопка , в которой хранится выбор пользователя
				echo '<input type="hidden" id="question_select" value="">';
				//Невидимая кнопка , в которой хранится ID опроса
				echo '<input type="hidden" id="poll_id" value="'.$arr['id'].'">';
				echo '</form>';
			} else {
				//Вывод результата
				//Запрос к базе данных
				$sql = $db->query("SELECT COUNT(id) AS count , id_question FROM polls_voting WHERE id_poll=".$arr['id']." GROUP BY id_question");
				$votes = array(); //Массив проголосовавших
				$total = 0;
				
				//Собираем данные об опросе
				while($vote = $db->get_row($sql) ) {
					$total += $vote['count'];
					$votes[$vote['id_question']] = $vote['count'];
				}
				
				//Собираем массив с результатом
				$results = array();
				while($question = $db->get_row($questions)) {
					$results[] = array($votes[$question['id']], $question['subject']);
				}
				
				if($arr['sort']) {
					function srt($a,$b) {
						if ($a[0] > $b[0]) return -1;
						if ($a[0] < $b[0]) return 1;
						return 0;
					}
					
					//Сортировка
					usort($results, srt);
				}
				
				//Счетчик
				$i = 0;
				
				//Выводим результат голосования
				echo '<table width="100%" class="results" id="results" cellpadding="3">';
				foreach($results as $result) {
					echo '<tr><td width="20%">'.htmlspecialchars($result[1]).'</td>
					<td width="60%"><div class="bar'.($i == 0 ? "max" : "").'"  name="'.($result[0] / $total * 100).'" id="poll_result">&nbsp;</div></td>
					<td><b>'.number_format(($result[0] / $total * 100), 2).'%</b></td></tr>';
					$i++;
				}
				echo '<tr><td><b>Всего:</b> '.number_format($total).'</td></tr>';
				echo '</table>';
				
			}
		}
		die();
	}
	
	/////////////////////////////////////////////////////
	//Отправка голоса
	/////////////////////////////////////////////////////
	if($_POST['do'] == "voting") {
		//Вариант ответа
		$question = (int)$_POST['question'];
		
		//Пустой голос 
		if($question == 0 ) {
			//Отправляем JSON - данные об успешной операции
			die(json_encode(array("status" =>1)));
		}
		
		$sql = $db->query("SELECT * FROM polls_questions WHERE id=".$question);
		if(!$db->num_rows($sql) ) {
			die(json_encode(array("status" => 0 , "msg"=>"Такого варианта ответа не было найдено")));
		}
		
		//ID опроса
		$id_poll = (int)$_POST['poll_id'];
		$sql = $db->query("SELECT * FROM polls WHERE id=".$id_poll);
		if(!$db->num_rows($sql) ) {
			die(json_encode(array("status" => 0 , "msg"=>"Опрос не найден , извините")));
		}
		
		//Проверяем , голосовал ли пользователь
		$sql = $db->query("SELECT * FROM polls_voting WHERE id_poll=".$id_poll." AND id_user=".$USER['id']);
		if($db->num_rows($sql) ) {
			die(json_encode(array("status" => 0 , "msg"=> "Вы уже голосовали за данный опрос")));
		}
		
		//Добавляем голос
		$db->query("INSERT INTO polls_voting (id_poll , id_user, id_question , date) VALUES (".$id_poll." , ".$USER['id']." , ".$question." , NOW() )");
		
		//Отправляем JSON - данные об успешной операции
		die(json_encode(array("status" =>1)));
	}
}
?>