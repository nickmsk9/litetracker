<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Функции комментирования
===================================================================
*/


//Форма добавления комментария
function addComment($type = "" ,$object_id = "" , $file = '') {
	global $USER ,$language , $rewrite;
	if($USER) {	
		// echo '<div id="addComment" style="display:none;padding:10; width:100">';
		echo '<form name="addComment" method="POST" action="comments.take.php"> ';
		// echo '<textarea name="text" style="width:100%;height:50"> </textarea>';
		textbb('text' , $_POST['descr'] ?? '',  '95%' , '300px');
		echo '<br><input value="'.$language['comments_1'].'"  type="submit" >';
		echo '<input type="hidden" value="'.$object_id.'" name="object_id">';
		echo '<input type="hidden" value="'.$type.'" name="type">';
		echo '<input type="hidden" value="'.$file.'" name="file">';
		echo '<input type="hidden" value="add" name="act">';
		echo '</form>';
		echo '<br>';
	}	
}

//Форма добавления комментария
function listComment($type = "" , $object_id = "" , $file = "" , $desc = 0) {
	global $USER , $PRIV , $config , $db, $language , $rewrite , $memcache;
	
	
	//Js functions
	echo '<script src="/public/js/comments.js"> </script>';
	
	//////////////////////////////////////////////////////////////////
	//Вывод комментариев
	//////////////////////////////////////////////////////////////////
	//Постраничная навигация
	$res = $db->query("SELECT * FROM comments_".$type."  WHERE id_".$type."=".$object_id."");
	
	$count = $db->num_rows();
	list($pagertop, $pagerbottom, $limit) = pager('20', $count, $file.'id='.$object_id.'&' ,  array('lastpagedefault' => 1)); //Делим на страницы
	
	
	$query = queryComment($type , $object_id ,$limit , $desc);
	$sql = $db->query($query);
	
	
	// echo ($USER ?  "<input type=\"button\" value=\"".$language['comments_1']."\" onClick=\"upCommentForm();return false;\">" : '');
	
	

	//Проверка, существуют ли комментарии
	if(!$db->num_rows($sql) ) {
		msg($language['comments_2'], '' , 'error');	
	} else {
		echo $pagertop;
		//Выводим в цикле комментарии
		while($arr  = $db->get_row($sql) )
        {
			$id = $arr['comment_id']; //Номер комментария
			$text = cleanhtml($arr['text']); //Текст комментария
			
			
			$user = get_user_info($arr['id_user']);
			$user_id = $user['id']; //Номер пользователя
			$avatar = ($user['avatar'] == "" ? '<center><img src="public/images/default_avatar.gif" border="0" width="50"></center>' : '<center><img src="public/avatars/'.$user['avatar'].'" border="0" width="50"></center>'); //Фотография пользователя
			
			$user_name = $user['name']; //Имя пользователя
			$user_class = $user['class']; //Класс пользователя
			$date = convent_date($arr['date']);
			
			$append_edit = ($arr['date_edit'] != '0000-00-00 00:00:00' ?   $language['comments_3'].' '.convent_date($arr['date_edit']) : ''); //Дата правки комментария
			
			require 'templates/'.$config['template'].'/tpl.comments.php';
		}
		
		echo $pagertop;	
	}
	
	
	//////////////////////////////////////////////////////////////////
	//Форма добавления
	//////////////////////////////////////////////////////////////////
	addComment($type , $object_id, $file );
	

	
}


//Запрос комментирования
function queryComment($type , $object_id , $limit , $desc) {

	//Query to database
	$query  = "SELECT comments_".$type.".* , comments_".$type.".id AS comment_id 
			   FROM comments_".$type."  
			   LEFT JOIN ".$type." ON ".$type.".id =  comments_".$type.".id_".$type."
			   WHERE comments_".$type.".id_".$type." = ".$object_id."
			   ORDER BY  comments_".$type.".date ".($desc ? 'DESC' : 'ASC')."
			   ".$limit."
			   ";
	return $query;		   
}

//Статусы
function comment_status() {
	global $language;
	if($_GET['status'] == '1') {
		msg($language['comments_6']);
	} elseif($_GET['status'] == '2') {
		msg($language['comments_7']);
		
	}elseif($_GET['status'] == '3') {
		msg($language['comments_8']);	
	}
}
?>
