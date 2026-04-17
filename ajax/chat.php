<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Ajax функции чата
===================================================================
*/
error_reporting(E_ALL ^ E_NOTICE);

if(($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') == 'XMLHttpRequest') {
	//Подключаем главный системный файл
	require '../system/init.php';
	global $language;
	header("Content-Type: text/html; charset=".$language['charset']."");
	
	//Запрещаем вывод
	if(!$PRIV['chat_view'] ) {
		msg($language['chat_10']);
		die();
	}
	
	////////////////////////////////////////////////////////////////////
	//Обновление чата
	////////////////////////////////////////////////////////////////////
	if($_POST['type'] == 'update') {
		$sql = $db->query("SELECT c.* 
					FROM chat  AS c 
					ORDER BY c.date DESC
					LIMIT 25");
		if(!$db->num_rows($sql) ) {
			msg($language['chat_4']);
		}else  {
			while($arr = $db->get_row($sql) ) {
				echo '<div id="message_'.$arr['id'].'">';
				echo '<table width="100%" cellpadding="0">';
				echo '<tr>';
				
			
				echo '<td width="7%">';
				if($PRIV['chat_delete']) {
					echo '<small><a href="javascript:void(0);" onClick="confirm_message_delete('.$arr['id'].')"><img src="public/images/broom.png" border="0" title="'.$language['chat_8'].'"/></a></small>&nbsp';
				}

				echo '<small><a href="'.profile_href($arr['id_user']).'"><img src="public/images/users.png" border="0" title="'.$language['chat_9'].'"/></a></small>&nbsp';
				echo '</td>';
				
				echo '<td width="10%">';
			
				echo '<a href="javascript:void(0);" onclick="parent.document.chat.text_chat.focus();parent.document.chat.text_chat.value=\'[b]'.$arr['username'].'[/b]: \'+parent.document.chat.text_chat.value;return false;">'.get_user_color($arr['userclass'] , $arr['username']).'</a>:';
				
				echo '</td>';
				
				//Заменяем bb - коды на html - коды
				

				
				echo '<td width="70%">';
				echo format_comment($arr['text']);
				// echo $arr['text'];
				echo '</td>';
				
				echo '<td width="30%">';
				echo '<div style="float:right">';
				
				// echo '<small>'.convent_date($arr['date']).'</small>&nbsp';
				$date = explode(' ' , $arr['date']);
				$time = explode(':' , $date[1]);
				echo  '<small>'.$time[0].':'.$time[1].'</small>';
				

				
				echo '</div>';
				echo '</td>';
				
			
				echo '</tr>';
				echo '</table>';
				
				echo '</div>';
			}
		}	
		die();
	}
	
	
	////////////////////////////////////////////////////////////////////
	//Отправка сообщений
	////////////////////////////////////////////////////////////////////
	if($_POST['type'] == 'send') {
	
		if(!$USER) {
			die();
		}
		
		//Инвервал 
		if(( time() -  $USER['last_chat']) < $config['chat_limit']) {
			msg(sprintf($language['chat_5']  , $config['chat_limit']) );
			die();
		}
		
		
	
		$text = trim((string) ($_POST['text'] ?? ''));
		
		if(empty($text)) {
			msg($language['chat_6']);
			die();
		} 
		
		//Длина сообщения
		if(strlen($text) > $config['chat_limit_text']) {
			msg(sprintf($language['chat_6']  , $config['chat_limit_text']) );
			die();
		}
		
		
		
		$db->query("INSERT INTO chat(text , date , id_user,username,userclass) VALUES ('".$db->safesql($text)."' , NOW() , ".$USER['id'].", '".$USER['name']."', '".$USER['class']."' )");

		
		echo '';
		die();
	}
	
	
	////////////////////////////////////////////////////////////////////
	//Удаление сообщения
	////////////////////////////////////////////////////////////////////
	if($_POST['type'] == 'delete') {
		//Только администрации
		if(!$PRIV['chat_delete']) {
			die();
		}
		$id = (int)$_POST['id'];
		$db->query("SELECT * FROM chat WHERE id=".$id);
		if($db->num_rows() ) {
			$db->query("DELETE FROM chat WHERE id=".$id);
		}
		
		die();
	}
	
	
	////////////////////////////////////////////////////////////////////
	//Очистка чата
	////////////////////////////////////////////////////////////////////
	if($_POST['type'] == 'clear') {
		//Только администрации
		if(!$PRIV['chat_clear']) {
			die();
		}
		$db->query("DELETE FROM chat");
		
		msg($language['chat_4']);
		die();
	}
	
	
	
}
?>
