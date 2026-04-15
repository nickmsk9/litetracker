<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Обработка комментариев
===================================================================
*/

require 'system/init.php';


//Проверяем пользователя
is_login();



$type = (string)$_REQUEST['type']; //Тип комментирования
$object_id = (int)$_REQUEST['object_id']; //Номер объекта
$file = (string)$_REQUEST['file']; //Файл
$file_explode = explode('?' , $file);
if(!is_file($file_explode[0])) {
	err($language['default_1'] , $language['comments_14']  , 1);
}

//Проверяем объект
$db->query("SELECT * FROM ".$type." WHERE id=".$object_id."" , 0);
if(!@$db->num_rows() ) {
	err($language['default_1'] , $language['comments_8'] , 1);
}

//////////////////////////////////////////////////////////////
//Добавление комментария
//////////////////////////////////////////////////////////////
if($_REQUEST['act'] == 'add') {
	//Текст комментария
	$text = trim($_REQUEST['text']);
	if(empty($text) ) {
		err($language['default_1'] , $language['comments_9'] ,1);
	}
	
	$table_name = 'comments_'.$type; //Имя таблицы
	$object_name = 'id_'.$type; //К чему мы добавляем комментарий
	
	
	//Добавляем комментарий
	$db->query("INSERT INTO ".$table_name." (id_user , ".$object_name." , date , text) VALUES (".$USER['id']." , ".$object_id." , NOW() , '".$db->safesql($text)."')" , 0);
	header('Location:'.$file.'id='.$object_id.'&status=1');
	die();
}
//////////////////////////////////////////////////////////////
//Удаление комментария
//////////////////////////////////////////////////////////////
if($_REQUEST['act'] == 'delete' && $_REQUEST['id_comment']) {
	$id_comment = (int)$_REQUEST['id_comment'];
	$db->query("SELECT id_user FROM comments_".$type." WHERE id=".$id_comment , 0);
	$arr = $db->get_row();
	
	//Проверяем права
	if($USER['id'] != $arr['id_user'] && !$PRIV['comments_delete']) {
		err($language['default_1'] , $language['comments_10'] , 1);
	}
	
	//Удаляем комментарий
	$db->query("DELETE FROM comments_".$type." WHERE id=".$id_comment." AND id_".$type."=".$object_id , 0);
	header('Location:'.$file.'id='.$object_id.'&status=2');
	die();

}

//////////////////////////////////////////////////////////////
//Редактирование комментария
//////////////////////////////////////////////////////////////
if($_REQUEST['act'] == 'edit' && $_REQUEST['id_comment']) {
	$id_comment = (int)$_REQUEST['id_comment'];
	$db->query("SELECT * FROM comments_".$type." WHERE id=".$id_comment , 0);
	$arr = $db->get_row();
	
	//Проверяем права
	if($USER['id'] != $arr['id_user'] && !$PRIV['comments_edit']) {
		err($language['default_1'] , $language['comments_11'] , 1);
	}
	
	//Обработка
	if($_POST) {
		$update = array();
		
		//Текст
		$text  = trim($_REQUEST['text']);
		if($arr['text'] != $text) {
			if(empty($text) ) {
				err($language['default_1'] , $language['comments_9'] ,1);
			}
			$update[] = 'text="'.$db->safesql($text).'"';
			$update[] = 'id_user_edit='.$USER['id'];
			$update[] = 'date_edit=NOW()';
		}
		
		$table_name = 'comments_'.$type; //Имя таблицы
		$object_name = 'id_'.$type; //К чему мы добавляем комментарий
		
		//Обновляем комментарий
		if(count($update) ) {
			$db->query("UPDATE ".$table_name." SET ".implode(',' ,$update)." WHERE id=".$id_comment , 0);
		}
		header('Location:'.$file.'id='.$object_id.'&status=3');
		die();
	}
	
	//Форма
	head($language['comments_12']);
	begin_frame($language['comments_12']);
	echo '<form name="addComment" method="POST" action="comments.take.php" >';
	
	textbb('text' , $arr['text'],  '90%' , '300');
	echo '<br>';
	echo '<input value="'.$language['comments_4'].'"  type="submit" >&nbsp';
	echo '<input value="'.$language['default_5'].'"  type="button" onClick="history.go(-1);">';
	echo '<input type="hidden" value="'.$object_id.'" name="object_id">';
	echo '<input type="hidden" value="'.$type.'" name="type">';
	echo '<input type="hidden" value="'.$id_comment.'" name="id_comment">';
	echo '<input type="hidden" value="'.$file.'" name="file">';
	echo '<input type="hidden" value="edit" name="act">';
	echo '</form>';
	end_frame();
	foot();
	die();
}
?>