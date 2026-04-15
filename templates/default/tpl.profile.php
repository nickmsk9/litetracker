<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Профиля
////////////////////////////////////////////////////////
//$id - id пользовтаеля

begin_frame('Просмотр профиля');
?>
<link href="public/css/torrenttable.css" rel="StyleSheet" type="text/css">
<table width="100%" cellpadding="3">
<tr>

<td valign="top">
<?
// begin_frame($language['profile_6']);
?>
<table cellspacing="7" cellpadding="0" class="tt">

     <tbody>	
	 
	
	
	<tr>
     <td class="label">Ник:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$name;?> <?=$online;?>
      </div>
     </td>
    </tr>
	
	
	<tr>
     <td class="label"><?=$language['profile_7'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$date;?>
      </div>
     </td>
    </tr>
	<? if($PRIV['setting_user'] || $arr['id'] == $USER['id']) { ?>
	<tr>
     <td class="label"><?=$language['profile_8'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$email;?> <div style="float:right">(<?=$language['profile_9'];?>)</div>
      </div>
     </td>
    </tr>

	<tr>
     <td class="label"><?=$language['profile_10'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$ip;?> <div style="float:right">(<?=$language['profile_9'];?>)</div>
      </div>
     </td>
    </tr>	
	<? } ?>
	
	<tr>
     <td class="label"><?=$language['profile_11'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$last_access;?>
      </div>
     </td>
    </tr>

	<tr>
     <td class="label"><?=$language['profile_12'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$banned;?>
      </div>
     </td>
    </tr>	
	
     </tbody></table>
<?
// end_frame();

// begin_frame($language['profile_13']);
?>
<br><br>
<table cellspacing="7" cellpadding="0" class="tt">

     <tbody>	
	 
	
	<tr>
     <td class="label"><?=$language['setting_33'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$sex;?>
      </div>
     </td>
    </tr>
	
	<? if($USER) { ?>
		<? if($icq) { ?>
		<tr>
		 <td class="label"><?=$language['setting_38'];?>:</td>
		 <td class="data">
		  <div class="dataWrap">
		  <?=$icq;?>
		  </div>
		 </td>
		</tr>	
		<? } ?>
		
		<? if($skype) { ?>
			<tr>
			 <td class="label">Skype:</td>
			 <td class="data">
			  <div class="dataWrap">
			  <?=$skype;?>
			  </div>
			 </td>
			</tr>	
		<? } ?>
		
		
		<? if($id_vkontakte) { ?>
			<tr>
			 <td class="label">Я ВКонтакте:</td>
			 <td class="data">
			  <div class="dataWrap">
				<a href="http://vk.com/id<?=$id_vkontakte;?>" target="_blank">http://vk.com/id<?=$id_vkontakte;?></a>
			  </div>
			 </td>
			</tr>	
		<? } ?>
		
		<? if(!empty($website) ) { ?>
		<tr>
		 <td class="label"><?=$language['setting_36'];?>:</td>
		 <td class="data">
		  <div class="dataWrap">
		  <?=$website;?>
		  </div>
		 </td>
		</tr>
		<? } ?>
		
	<? } ?>
	
	
	<tr>
     <td class="label"><?=$language['setting_41'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$downloaded;?>
      </div>
     </td>
    </tr>	
	<tr>
     <td class="label"><?=$language['setting_43'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$uploaded;?>
      </div>
     </td>
    </tr>		
	
     </tbody></table>
<?
// end_frame();
?>


</td>

<td width="180" valign="top">
<?
//Постер
// begin_frame();

//Аватар
echo '<script type="text/javascript" src="/public/js/jquery.lightbox.js"></script>
<link rel="stylesheet" type="text/css" href="/public/css/lightbox.css" />';

echo '<ul id="avatar" class="gallery">';
echo ' <li class="gallery"> <a rel="lightbox-tour" href="public/avatars/'.$arr['avatar'].'" title="Фотография пользователя" >'.$avatar.'</a></li>';
echo '</ul>';

echo '
	<script type="text/javascript">
	
		$("#avatar a").lightbox();
		$.Lightbox.construct({
			"speed": 500,
			"show_linkback": true,
			"keys": {
				close:	"q",
				prev:	"z",
				next:	"x"
			},
			"opacity": 0.2,
			text: {
				image:		"Картинка",
				of:			"из",
				close:		"Закрыть",
				closeInfo:	"Завершить просмотр можно, кликнув мышью вне картинки.",
				help: {
					close:		"Закрыть",
					interact:	"Закрыть скриншоты"
				},
				about: {
					text: 	"",
					title:	"",
					link:	""
				}
			},
			files: {
				images: {
					prev:		"public/images/lightbox/prev.gif",
					next:		"public/images/lightbox/next.gif",
					blank:		"public/images/lightbox/blank.gif",
					loading:	"public/images/lightbox/loading.gif"
				}
			}
		});
	
	</script>';





get_user_rating($arr['uploaded'] ,  $arr['downloaded']);
// end_frame();

//Функции
// begin_frame($language['profile_15']);
echo ($USER['id'] == $arr['id'] || $PRIV['setting_user'] ? '<a class="proleft" href="my.setting.php?id='.$id.'">'.$language['profile_16'].'</a>' : '');
echo ($USER['id'] != $arr['id'] ? '<a class="proleft" href="my.mail.php?act=send&id_user='.$arr['id'].'">'.$language['profile_17'].'</a>' : '');

echo '<a class="proleft" href="browse.php?search=&id_user='.$arr['id'].'">'.$language['profile_18'].'</a>';


if($USER && $arr['id'] != $USER['id'])  {
	$friends = check_friend($arr['id'] , $USER['id']);
	if($friends['count']) {
		echo '<a class="proleft" href="my.friends.php?act=check&friendid='.$arr['id'].'&check=delete">Убрать из друзей</a>';
	} else { 
		echo '<a class="proleft" href="my.friends.php?act=add&friendid='.$arr['id'].'">Добавить в друзья</a>';
	}
}

echo ($PRIV['EDIT_PRIV'] ? '<a class="proleft" href="my.mail.php?id_user='.$arr['id'].'"><b>Читать сообщения</b></a>' : '');




if($config['vkontakte_profile'] && $arr['use_vkontakte']) {
	vkontakte_profile($id_vkontakte);
}

// end_frame();



?>
</td>

</tr>

</table>

<?


end_frame();


begin_frame('Друзья пользователя ');

if($friends_arr) {
	echo '<table>';
	foreach($friends_arr AS $rows) { 
		$user  = get_user_info($rows['userid']);
		echo '<td align="center"><a href="profile.php?id='.$user['id'].'">'.($user['avatar'] ? '<img src="public/avatars/'.$user['avatar'].'" width="50" height="50">' : '<img src="public/images/default_avatar.gif" width="50" width="50">').'</a>
		<br><a href="profile.php?id='.$user['id'].'">'.get_user_color($user['class'] , $user['name']).'</a></td>';
	}
	
	echo '<input type="button" value="Посмотреть всех" onClick="window.location.href=\'my.friends.php?id='.$id.'\'">';
	echo '</table>';
} else { 
	msg('Внимание' , "Пользователь ни с кем не дружит :(");
}
end_frame();


//Комментарии
begin_frame('Стена пользователя');
listComment('users' , $id , 'profile.php?' , 1);
end_frame();
?>
