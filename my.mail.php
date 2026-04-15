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

$_GET['act'] = (string)$_GET['act'];
if(!$_GET['act']) {
	$_GET['act'] = 'in_message';
}
//////////////////////////////////////////////////////////////
//Восстановление сообщение
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'restore' && $_GET['id']) {
	$id = (int)$_GET['id'];
	$db->query("SELECT * FROM mail WHERE id=".$id." AND (id_user_in=".$USER['id']." OR id_user_out=".$USER['id'].")");
	if(!$db->num_rows()) {
		err('Ошибка' , 'Данного сообщения не существует' , 1);
	}
	$arr = $db->get_row();
	
	//Если пользователь получатель
	if($arr['id_user_in'] == $USER['id'] && $arr['delete_in'] == 1) {
		$db->query("UPDATE mail SET delete_in='0' WHERE id=".$id);
		if(!$arr['reading']) {
			$db->query("UPDATE users SET num_messages=(num_messages + 1) WHERE id=".$USER['id']);
			$memcache->delete('user_'.$USER['id'] , 0);
		}
			
	}
	
	//Если пользователь отправитель
	if($arr['id_user_out'] == $USER['id'] && $arr['delete_out'] == 1) {
		$db->query("UPDATE mail SET delete_out='0' WHERE id=".$id);
	}
	
	// header("Location:my.mail.php?status=4&act=".($arr['id_user_in'] == $USER['id'] ? 'in_message' : 'out_message')."");
	header("Location:my.mail.php?status=4&act=".($_GET['type'] == 'in_message'  ? 'in_message' : 'out_message')."");
	die();
}

//////////////////////////////////////////////////////////////
//Удаление сообщение
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'del' && $_GET['id']) {
	$id =  (int)$_GET['id'];
	$db->query("SELECT * FROM mail WHERE id=".$id." AND (id_user_in=".$USER['id']." OR id_user_out=".$USER['id'].")");
	if(!$db->num_rows()) {
		err('Ошибка' , 'Данного сообщения не существует' , 1);
	}
	$arr = $db->get_row();
	
	//Если кто - то  уже удалил сообщение, удаляем его из базы
	// if($arr['delete_in'] || $arr['delete_out']) {
		// $db->query("DELETE FROM mail WHERE id=".$id);
		// header("Location:my.mail.php?status=2");
		// die();
	// }
	
	//Если пользователь получатель
	if($arr['id_user_in'] == $USER['id'] && $arr['delete_in'] == 0) {
		$db->query("UPDATE mail SET delete_in='1' WHERE id=".$id);
		if(!$arr['reading'] && $USER['num_messages'] > 0) {
			$db->query("UPDATE users SET num_messages=(num_messages - 1) WHERE id=".$USER['id']);
			$memcache->delete('user_'.$USER['id'] , 0);
		}
		$arr['delete_in'] = 0;
		
	}
	
	//Если пользователь отправитель
	if($arr['id_user_out'] == $USER['id'] && $arr['delete_out'] == 0) {
		$db->query("UPDATE mail SET delete_out='1' WHERE id=".$id);
		$arr['delete_out'] = 0;
	}
	
	//Если обе стороны удалили сообщение, удаляем его из базы
	if($arr['delete_in'] == 1 && $arr['delete_out'] == 1) {
		$db->query("DELETE FROM mail WHERE id=".$id);
		header("Location:my.mail.php?status=2");
		
	} else {
		header("Location:my.mail.php?status=3&id_message=".$id."&act=".($_GET['type'] == 'in_message'  ? 'in_message' : 'out_message')."");
		// header("Location:my.mail.php?status=3&id_message=".$id."&act=".($arr['id_user_in'] == $USER['id'] ? 'in_message' : 'out_message')."");
	}
	
	die();
}

//////////////////////////////////////////////////////////////
//Вывод сообщения
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'view' && $_GET['id']) {
	
	//Возможность читать директорам
	if($_GET['id_user'] && $PRIV['EDIT_PRIV']) {
		$id_user = (int)$_GET['id_user'];
		$check_user = $db->query("SELECT * FROM users WHERE id=".$id_user);
		if(!$db->num_rows($check_user) ) {
			err('Ошибка' , 'Данного пользователя не найдено' , 1);
		}	
		
	} else { 
		$id_user = $USER['id'];
	}

	
	$id = (int)$_GET['id'];
	$db->query("SELECT m.* , u.name AS user_name , u.class , u.id AS id_user , u.avatar FROM mail AS m 
				LEFT JOIN users AS u ON u.id = ".($_GET['type'] == 'out_message' ? 'm.id_user_in'  : 'm.id_user_out'  )." 
				WHERE m.id=".$id." AND (m.id_user_in=".$id_user." OR m.id_user_out=".$id_user.")");
	if(!$db->num_rows() ) {
		err('Ошибка', 'Данного сообщения не существует' , 1);
	}
	
	$arr = $db->get_row();
	
	//Делаем сообщение прочитаным
	if(!$arr['reading'] && $arr['id_user_in'] == $USER['id']) {
		$db->query("UPDATE users SET num_messages=(num_messages - 1) WHERE id=".$USER['id']);
		$db->query("UPDATE mail SET reading='1' WHERE id='".$id."'");
		
		$USER['num_messages'] = ($USER['num_messages'] - 1);  //Удаляем из массива одно непрочитанное сообщение
		$memcache->delete('user_'.$USER['id'] , 0);
	}
	
	head('Просмотр сообщения');
	begin_frame('Просмотр сообщения');
	?>
	
		
	
	
	<form action="my.mail.php?act=send&id_user=<?=$arr['id_user'];?>&id_message=<?=$id;?>" method="post">
	
	<table width="100%" cellspacing="7" cellpadding="0" border="0" align="center">
	<tbody>
		<tr>
		<th rowspan="10" valign="top" width="40%">
		<?
		if($arr['id_user'] != 0) {
			echo '<a href="profile.php?id='.$arr['id_user'].'">'.($arr['avatar'] ? '<img src="public/avatars/'.$arr['avatar'].'" width="70">' : '<img src="public/images/default_avatar.gif" width="70">').'</a>';
		} else {
			echo '<img src="public/images/default_avatar.gif" width="70">';
		}
		?>
		</th>
		</tr>
		<tr >
		<td class="ta_r">
		 <span class="grey"><?=($_GET['type'] == 'out_message' ? 'Кому:' : 'От:');?></span>
		</td>
		<td style="padding: 0px;">
			<?
			if($arr['id_user'] != 0) {
				echo '<a href="profile.php?id='.$arr['id_user'].'">'.get_user_color($arr['class'] ,$arr['user_name']).'</a>';
			} else {
				echo 'System';
			}	
			?>	
		 
		</td><td>
	   </td></tr>
		<tr>
		<td class="ta_r" width="10%">
		 <span class="grey" width="1%">Тема:</span>
		</td>
		<td style="padding: 0px;">
			<?=htmlspecialchars($arr['name']);?>
		</td><td>
	   </td></tr>
	   
	   	<tr>
		<td class="ta_r" valign="top">
		 <span class="grey">Сообщение:</span>
		</td>
		<td style="padding: 0px;">
		 <?=format_comment($arr['text']);?>
		</td><td>
	   </td></tr>
	   
	   
	  
	
	</tbody>
	</table>
	
	
	 <? if($arr['id_user'] != 0 ) { ?>
		<table align="center">
	   	<tr>
		
		<td style="padding: 0px;" colspan="2">
		 <?=textbb('text' , '' ,  '100%' , '300');?>
		</td><td>
	   </td></tr>
	   

	   	<tr>
		<td class="ta_r">
		 <span class="grey"></span>
		</td>
		<td style="padding: 0px;">
		 <input type="submit" value="Ответить">
		</td><td>
	   </td></tr>
	   </table>
		<? } ?>
	
	</form>
	<?
	end_frame();
	foot();
	die();
}




//////////////////////////////////////////////////////////////
//Отправка сообщений
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'send' && $_GET['id_user']) {
	//ID Пользователя
	$id_user = (int)$_GET['id_user'];
	if($id_user == $USER['id']) {
		err('Ошибка' , 'Вы не можете отправлять сообщения самому себе' , 1);
	}
	
	$db->query("SELECT * FROM users WHERE id=".$id_user);
	if(!$db->num_rows() ) {
		err('Ошибка' , 'Данного получателя не существует' , 1);
	}
	$arr = $db->get_row();
	
	
	
	//Обработка
	if($_POST) {
	
		//Тема сообщения
		
		$id_message = (int)$_GET['id_message'];
		
		//Ответ на сообщение
		if($id_message) {
			$db->query("SELECT * FROM mail WHERE id=".$id_message." AND (id_user_in=".$USER['id']." OR id_user_out=".$USER['id'].")");	
			if(!$db->num_rows() ) {
				err('Ошибка' , 'Данного сообщения не существует' , 1);
			}
			$arr = $db->get_row();
			
			//Добавляем к теме фразу: Re
			$name = 'Re:'.$arr['name'];
		} else {
			//Написать сообщение
			$name = trim($_POST['name']);
			if(empty($name) ) {
				$name = 'Re:';
			}
		}
		
		//Сообщение
		$text =  trim($_POST['text']);
		if(empty($text) ) {
			err('Ошибка' , 'Вы не ввели текст сообщения' , 1);
		}
		
		//Добавляем сообщение
		$db->query("INSERT INTO mail (name ,text , date , id_user_in , id_user_out) VALUES('".$db->safesql($name)."' , '".$db->safesql($text)."' , NOW() , ".$id_user." , ".$USER['id'].")");
		$db->query("UPDATE  users SET num_messages=(num_messages+1) WHERE id=".$id_user);
		$memcache->delete('user_'.$id_user , 0);
		header("Location:my.mail.php?status=1");
		die();
	}
	
	//Вывод формы
	head('Отправка сообщения');
	begin_frame('Отправка сообщения');
	?>
	<form action="my.mail.php?act=send&id_user=<?=$id_user;?>" method="post">
	<table width="100%" cellspacing="7" cellpadding="0" border="0" align="center">
	<tbody>
		<tr>
		<td class="ta_r" width="10%">
		 <span class="grey">Кому:</span>
		</td>
		<td style="padding: 0px;">
		 <a href="profile.php?id=<?=$arr['id'];?>"><?=get_user_color($arr['class'] , $arr['name']);?></a>
		</td><td>
	   </td></tr>
		<tr>
		<td class="ta_r">
		 <span class="grey">Тема:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" style="margin: 0px;" size="55"  name="name" class="inputText" value="<?=htmlspecialchars($_POST['name']);?>">
		</td><td>
	   </td></tr>
	   
	   	<tr>
		
		<td style="padding: 0px;" colspan="2">
		
		 <?=textbb('text' , $_POST['text'],  '95%' , '300');?>
		</td><td>
	   </td></tr>
	   
	   	<tr>
	
		<td style="padding: 0px;" colspan="2">
		 <input type="submit" value="Отправить">
		</td><td>
	   </td></tr>
	
	</tbody>
	</table>
	</form>
	<?
	end_frame();
	foot();
	die();
	
}

//////////////////////////////////////////////////////////////
//Мои сообщения (Полученные/Отправленные)
//////////////////////////////////////////////////////////////

//Возможность читать директорам
if($_GET['id_user'] && $PRIV['EDIT_PRIV']) {
	$id_user = (int)$_GET['id_user'];
	$check_user = $db->query("SELECT * FROM users WHERE id=".$id_user);
	if(!$db->num_rows($check_user) ) {
		err('Ошибка' , 'Данного пользователя не найдено' , 1);
	}	
	
} else { 
	$id_user = $USER['id'];
}




head('Мои сообщения');

echo '<link type="text/css" href="public/css/mail.css" rel="stylesheet">';

//Статусы
if($_GET['status'] == '1') {
	msg('Успешно' , 'Сообщение успешно отправлено');
}elseif($_GET['status'] == '2') {
	msg('Успешно' , 'Сообщение успешно удалено');
}elseif($_GET['status'] == '3' && $_GET['id_message']) {
	$id_message = (int)$_GET['id_message'];
	msg('Успешно' , 'Сообщение успешно удалено <br> <a href="my.mail.php?act=restore&id='.$id_message.'&type='.htmlspecialchars($_GET['act']).'">Восстановить</a>');
}elseif($_GET['status'] == '4') {
	msg('Успешно' , 'Сообщение успешно восстановлено');
}





//Выводим функции
begin_frame('Функции');
echo '<input type="button" value="Полученные" onCLick="window.location.href=\'my.mail.php?act=in_message'.($_GET['id_user'] ? '&id_user='.(int)$_GET['id_user'] : '').'\'">&nbsp';
echo '<input type="button" value="Отправленные" onCLick="window.location.href=\'my.mail.php?act=out_message'.($_GET['id_user'] ? '&id_user='.(int)$_GET['id_user'] : '').'\'">&nbsp';
end_frame();

begin_frame('Мои сообщения');


//Постраничная навигация
$db->query("SELECT m.*  
			FROM mail AS m
			WHERE ".($_GET['act'] == 'out_message' ? 'm.id_user_out='.$id_user  :  'm.id_user_in='.$id_user)."  
			AND ".($_GET['act'] == 'out_message' ?  'delete_out="0"'  :  'delete_in="0"')." 
			" , 1);
						
$count = $db->num_rows();
list($pagertop, $pagerbottom, $limit) = pager('50', $count, 'my.mail.php?act='.$_GET['act'].'&id_user='.$id_user.'&');

$sql  = $db->query("SELECT m.* , u.name AS user_name , u.class AS user_class , u.id AS id_user , u.avatar
		   FROM mail AS m 
		   LEFT JOIN users AS u ON u.id = ".($_GET['act'] == 'out_message' ?  'm.id_user_in'  :  'm.id_user_out')."  
		   WHERE ".($_GET['act'] == 'out_message' ? 'm.id_user_out='.$id_user  :  'm.id_user_in='.$id_user)." 
		   AND ".($_GET['act'] == 'out_message' ?  'delete_out="0"'  :  'delete_in="0"')." 
		   ORDER BY m.date DESC
		   ".$limit."");
		   
if(!$db->num_rows($sql) ) {
	msg('Извините' , 'Сообщений не найдено');
} else {
	echo $pagertop;
	?>
		<table width="100%" cellspacing="0" cellpadding="3" border="0" align="center" class="mailbox" >
		<tbody>
			
			<tr>
				<th width="1%">&nbsp </th>
				<th width="10%"><?=($_GET['act'] == 'out_message' ? 'Получатель' : 'Отправитель');?></th>
				<th>Сообщение</th>
				<th width="10%">Действия</th>
				
			</tr>
			
		<?
	
	while($arr = $db->get_row($sql) ) {
	
		//Название сообщения
		if (strlen($arr['name']) > 30) {
			$arr['name'] = substr($arr['name'] , 0, 30).'...'; 
		}
		$arr['name'] = htmlspecialchars($arr['name']);
		
		//Текст сообщения
		if (strlen($arr['text']) > 100) {
			$arr['text'] = substr($arr['text'] , 0, 100).'...'; 
		}
		
			
			
		?>
		<tr <?=(!$arr['reading'] ? 'style="background:	#f7f7f7;"' : '');?>>
		
		<!--Отправитель-->
		<td valign="top" width="100" align="center">
			<?
			if($arr['id_user'] != 0) {
				echo '<a href="profile.php?id='.$arr['id_user'].'">'.($arr['avatar'] ? '<img src="public/avatars/small/'.$arr['avatar'].'">' : '<img src="public/images/default_avatar.gif" width="50">').'</a>';
			} else {
				echo '<img src="public/images/default_avatar.gif" width="50">';
			}
			?>
			
		</td>
		<td width="70" valign="top">
			<?
			if($arr['id_user'] != 0) {
				echo '<a href="profile.php?id='.$arr['id_user'].'">'.get_user_color($arr['user_class'] ,$arr['user_name']).'</a>';
			} else {
				echo 'System';
			}	
			?>	
			<div class="date"><?=convent_date($arr['date']);?></div>
		</td>
		
		<!--Сообщение-->
		<td valign="top" class="messageSnippet">
			<div><a href="my.mail.php?id=<?=$arr['id'];?>&act=view&type=<?=htmlspecialchars($_GET['act']);?><?=($id_user ? '&id_user='.$id_user : '');?>" class="messageSubject"><?=htmlspecialchars($arr['name']);?></a></div>
			<div><a href="my.mail.php?id=<?=$arr['id'];?>&act=view&type=<?=htmlspecialchars($_GET['act']);?><?=($id_user ? '&id_user='.$id_user : '');?>" class="messageBody"><?=cleanhtml($arr['text']);?></a></div>	
		</td>
		
		
		<!--Действия-->
		<td width="70">
		<small><a href="my.mail.php?act=del&id=<?=$arr['id'];?>&type=<?=htmlspecialchars($_GET['act']);?>">Удалить</a></small>
		<small><a href="my.mail.php?act=view&id=<?=$arr['id'];?>&type=<?=htmlspecialchars($_GET['act']);?>">Ответить</a></small>
		<!--<input type="button" value="Удалить" style="width:100" onCLick="window.location.href='my.mail.php?act=del&id=<?=$arr['id'];?>'">
		<br>
		<input type="button" value="Ответить" style="width:100" onCLick="window.location.href='my.mail.php?act=view&id=<?=$arr['id'];?>&type=<?=htmlspecialchars($_GET['act']);?>'">-->
		</td>
		</tr>
		
		<tr><td colspan="4"><hr></td></tr>
		<?
	}
	?>
	<tr>
			<td colspan="4"><?=$pagertop;?></td>
			</tr>
			</tbody></table>
	<?
	
}
		   
end_frame();
foot();
?>