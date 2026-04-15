<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Мои настройки
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверяем пользователя
is_login();

//Определяем номер пользователя
$id = (int)$_GET['id'];
if(!$id || $id == $USER['id'])
	$arr = $USER;
else {
	$arr = $db->super_query("SELECT * FROM users WHERE id='".$id."'");
	$priv = get_priv_info($arr['class']);
	
	if(!$PRIV['setting_user'] || $priv['EDIT_PRIV']) {
		err($language['default_1'] , $language['setting_1'] , 1);
	}
}	



//////////////////////////////////////////////////////////////
//Загрузить фотографию
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'foto') {
	head($language['setting_2']);
	
	begin_frame($language['setting_2']);
	?>
	<form  action="my.setting.take.php?act=foto&id=<?=$id;?>"  id="loginPage" method="post" enctype="multipart/form-data" >
	<table width="80%" cellspacing="7" cellpadding="0" border="0" align="center">
	   <tbody>
	   <tr>
		<td class="ta_r">
		 <?=($arr['avatar'] ? '<img src="public/avatars/'.$arr['avatar'].'" border="0" width="100px">' : '<img src="public/images/default_avatar.gif">');?>
		</td>
		<td style="padding: 0px;" valign="top">
			<input type="file" name="foto" size="30">
			<br><br><input type="submit" value="<?=$language['setting_2'];?>" > &nbsp <?=($arr['avatar'] ? ' <input type="button" value="'.$language['setting_3'].'" onClick="window.location.href=\'my.setting.take.php?id='.$id.'&act=foto_delete\'"> ' : '');?>
			<hr>
			<?=$language['setting_4'];?>
		</td><td>
	   </td>
	   </tr>
	
	  

	  </tbody></table>

	  </form>
	
	<?
	end_frame();
	
	foot();
	die();
}



//////////////////////////////////////////////////////////////
//Изменить пасскей
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'passkey') {
	head($language['setting_5']);
	
	begin_frame($language['setting_5']);
	msg($language['default_7'] , $language['setting_6']);
	?>
	<form  action="my.setting.take.php?act=passkey&id=<?=$id;?>"  id="loginPage" method="post">
	<table width="80%" cellspacing="7" cellpadding="0" border="0" align="center">
	   <tbody>
	   <tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['setting_7'];?>:</span>
		</td>
		<td style="padding: 0px;">
			<?=$arr['passkey'];?>
		</td><td>
	   </td>
	   </tr>
	  
	   <tr>
		<td>
		 &nbsp;
		</td>
		<td>
	<div style="height: 20px; margin: 5px 0px;">
		<input type="submit" value="<?=$language['setting_5'];?>" > &nbsp <input type="button" value="<?=$language['default_5'];?>" onClick="window.location.href='my.setting.php?id=<?=$id;?>'"> 
	</div>

		</td>
	   </tr>
	  

	  </tbody></table>

	  </form>
	
	<?
	end_frame();
	
	foot();
	die();
}



//////////////////////////////////////////////////////////////
//Изменить пароль
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'password') {
	head($language['setting_8']);
	
	begin_frame($language['setting_8']);
	msg($language['default_7'] , $language['setting_9']);
	?>
	<form  action="my.setting.take.php?act=password&id=<?=$id;?>"  id="loginPage" method="post">
	<table width="80%" cellspacing="7" cellpadding="0" border="0" align="center">
	   <tbody>
	   <? if(!$PRIV['setting_user']) { ?>
	   <tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['setting_9'];?>:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="password" style="margin: 0px;" size="25"  name="old_password" class="inputText" value="">
		</td><td>
	   </td>
	   </tr>
	   <? } ?>
	   <tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['setting_11'];?>:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="password" style="margin: 0px;" size="25"  name="new_password" class="inputText" value="">
		</td><td>
	   </td>
	   </tr>
	   
	   <tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['setting_12'];?>:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="password" style="margin: 0px;" size="25"  name="new_password_1" class="inputText" value="">
		</td><td>
	   </td>
	   </tr>

	   
	   <tr>
		<td>
		 &nbsp;
		</td>
		<td>
	<div style="height: 20px; margin: 5px 0px;">
		<input type="submit" value="<?=$language['setting_8'];?>" >
	</div>

		</td>
	   </tr>
	  

	  </tbody></table>

	  </form>
	
	<?
	end_frame();
	
	foot();
	die();
}


//////////////////////////////////////////////////////////////
//Изменить email
//////////////////////////////////////////////////////////////
if($_GET['act'] == 'email') {
	head($language['setting_13']);
	
	begin_frame($language['setting_13']);
	msg($language['default_7'] , $language['setting_14']);
	?>
	<form  action="my.setting.take.php?act=email&id=<?=$id;?>"  id="loginPage" method="post">
	<table width="80%" cellspacing="7" cellpadding="0" border="0" align="center">
	   <tbody>
	   <tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['setting_15'];?>:</span>
		</td>
		<td style="padding: 0px;">
		 <?=htmlspecialchars($arr['email']);?>
		</td><td>
	   </td>
	   </tr>
	   
	   <tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['setting_16'];?>:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" style="margin: 0px;" size="25"  name="email" class="inputText" value="">
		</td><td>
	   </td>
	   </tr>

	   
	   <tr>
		<td>
		 &nbsp;
		</td>
		<td>
	<div style="height: 20px; margin: 5px 0px;">
		<input type="submit" value="<?=$language['setting_13'];?>" >
	</div>

		</td>
	   </tr>
	  

	  </tbody></table>

	  </form>
	
	<?
	end_frame();
	
	foot();
	die();
}





//////////////////////////////////////////////////////////////
//Общий вид
//////////////////////////////////////////////////////////////
head($language['setting_17']);

if($_GET['status'] == '1') {
	msg($language['default_9'] , $language['setting_18']);
}elseif($_GET['status'] == '2') {
	msg($language['default_9'] , $language['setting_19']);
}elseif($_GET['status'] == '3') {
	msg($language['default_1'] , $language['setting_20'] , 'error');
}elseif($_GET['status'] == '4') {
	msg($language['default_9'] , $language['setting_21']);
}elseif($_GET['status'] == '5') {
	msg($language['default_9'] , $language['setting_22']);
}elseif($_GET['status'] == '6') {
	msg($language['default_1'] , $language['setting_23'] , 'error');
}elseif($_GET['status'] == '7') {
	msg($language['default_9'] , $language['setting_24']);
}elseif($_GET['status'] == '8') {
	msg($language['default_9'] , $language['setting_25']);
}elseif($_GET['status'] == '9') {
	msg($language['default_9'] , $language['setting_26']);
}elseif($_GET['status'] == '10') {
	msg($language['default_9'] ,$language['setting_27']);
}elseif($_GET['status'] == '11') {
	msg($language['default_9'] , $language['setting_28']);
}elseif($_GET['status'] == '12') {
	msg($language['default_9'] , $language['setting_29']);
}
elseif($_GET['status'] == '0') {
	msg($language['default_1'] , $language['setting_30'] , 'error');
}	 


begin_frame($language['setting_31']);
?>
<form  action="my.setting.take.php?id=<?=$id;?>"  id="loginPage" method="post">
<table width="80%" cellspacing="7" cellpadding="7" border="0" align="center">
   <tbody>
   <tr>
    <td class="ta_r">
     <span class="grey"><?=$language['setting_32'];?>:</span>
    </td>
    <td style="padding: 0px;">
     <input type="text" style="margin: 0px;" size="25"  name="name" class="inputText" value="<?=htmlspecialchars($arr['name']);?>">
    </td><td>
   </td>
   </tr>
   
    <tr>
    <td class="ta_r">
     <span class="grey"><?=$language['setting_33'];?>:</span>
    </td>
    <td style="padding: 0px;">
		<select name="sex" style="width:155">
		<option  <?=($arr['sex'] == 1 ? 'selected' : '');?> value="1"><?=$language['setting_34'];?></option>
		<option  <?=($arr['sex'] == 0 ? 'selected' : '');?> value="0"><?=$language['setting_35'];?></option>
		</select>
    </td><td>
   </td>
   </tr>
   
	<tr>
    <td class="ta_r">
     <span class="grey"><?=$language['setting_36'];?>:</span>
    </td>
    <td style="padding: 0px;">
     <input type="text" style="margin: 0px;" size="25"  name="website" class="inputText" value="<?=htmlspecialchars($arr['website']);?>">
	 <br><small><?=$language['setting_37'];?></small>
    </td><td>
   </td>
   </tr>
   
   
   <tr>
    <td class="ta_r">
     <span class="grey"><?=$language['setting_38'];?>:</span>
    </td>
    <td style="padding: 0px;">
     <input type="text" style="margin: 0px;" size="25"  name="icq" class="inputText" value="<?=htmlspecialchars($arr['icq']);?>">
	 <br><small><?=$language['setting_39'];?></small>
    </td><td>
   </td>
   </tr>
   
   
   <tr>
    <td class="ta_r">
     <span class="grey">Skype:</span>
    </td>
    <td style="padding: 0px;">
     <input type="text" style="margin: 0px;" size="25"  name="skype" class="inputText" value="<?=htmlspecialchars($arr['skype']);?>">
	 <br><small>Логин Skype должен соответствовать стандартам !</small>
    </td><td>
   </td>
   </tr>
   
   <tr>
    <td class="ta_r">
     <span class="grey">ID Vkontakte:</span>
    </td>
    <td style="padding: 0px;">
     <input type="text" style="margin: 0px;" size="25"  name="id_vkontakte" class="inputText" value="<?=htmlspecialchars($arr['id_vkontakte']);?>">
	 <br><small><?=$language['setting_39'];?></small>
    </td><td>
   </td>
   </tr>
   
   <? if($config['vkontakte_profile'] && $config['vkontakte_use']) { ?>
    <tr>
    <td class="ta_r">
     <span class="grey">Показывать профиль:</span>
    </td>
    <td style="padding: 0px;">
     <input type="checkbox"  name="use_vkontakte" class="inputText" value="1" <?=($arr['use_vkontakte'] ? 'checked' : '');?> /> Показывать профиль ВКонтакте (Если введен ID Vkontakte)
    </td><td>
   </td>
   </tr>
   <? } ?>
   
	<!--Администрация-->
	<? if($PRIV['setting_user']) { ?>
	
		<? if($USER['id'] != $arr['id'] && $PRIV['EDIT_PRIV']) { ?>
			<tr>
			<td class="ta_r">
			 <span class="grey"><?=$language['setting_40'];?>:</span>
			</td>
			<td style="padding: 0px;">
			<select name="class">
			<? 
			//Выводим список классов
			$classes = get_classes_list();
			foreach($classes AS $class) {
				echo '<option '.($class['id'] == $arr['class'] ? 'selected' : '').' value="'.$class['id'].'">'.htmlspecialchars($class['NAME']).'</option>';	
			}
			?>
			
			 </select>
			
			</td><td>
		   </td>
		   </tr>
	   <? } ?>
	   
	    <tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['setting_41'];?>:</span>
		</td>
		<td style="padding: 0px;">
		<select name="down_command">
		<option value="+">+</option>
		<option value="-">-</option>
		</select>
		
		<input type="text" style="margin: 0px;" size="10"  name="downloaded" class="inputText" value=""> 
		
		<select name="down_format">
		<option value="mb">MB</option>
		<option value="gb">GB</option>
		</select>

		
		
		 <br> <small><?=$language['setting_42'];?> <?=mksize($arr['downloaded']);?></small>
		 
		</td><td>
	   </td>
	   </tr>

	   <tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['setting_43'];?>:</span>
		</td>
		<td style="padding: 0px;">
		 
		<select name="up_command">
		<option value="+">+</option>
		<option value="-">-</option>
		</select>
		
		<input type="text" style="margin: 0px;" size="10"  name="uploaded" class="inputText" value=""> 
		
		<select name="up_format">
		<option value="mb">MB</option>
		<option value="gb">GB</option>
		</select>

		
		
		 <br> <small><?=$language['setting_44'];?> <?=mksize($arr['uploaded']);?></small>
		</td><td>
	   </td>
	   </tr>	   
   
   <? } ?>
   <tr>
    <td>
     &nbsp;
    </td>
    <td>
<div style="height: 20px; margin: 5px 0px;">
	<input type="submit" value="<?=$language['setting_45'];?>" >
</div>

    </td>
   </tr>
  

  </tbody></table>

  </form>
<?
end_frame();


begin_frame($language['setting_46']);
?>
<table width="80%" cellspacing="7" cellpadding="0" border="0" align="center">
 <tr>
    <td class="ta_r">
     <span class="grey"><?=$language['setting_47'];?>:</span>
    </td>
    <td style="padding: 0px;">
		<input type="button" value="<?=$language['setting_8'];?>" onClick="window.location.href='my.setting.php?id=<?=$id;?>&act=password'">
    </td><td>
   </td>
   </tr>
   
   
   <tr>
    <td class="ta_r">
     <span class="grey"><?=$language['setting_48'];?>:</span>
    </td>
    <td style="padding: 0px;">
		<input type="button" value="<?=$language['setting_13'];?>" onClick="window.location.href='my.setting.php?id=<?=$id;?>&act=email'">
    </td><td>
   </td>
   </tr>
   
   	<tr>
    <td class="ta_r">
     <span class="grey"><?=$language['setting_49'];?>:</span>
    </td>
    <td style="padding: 0px;">
		<input type="button" value="<?=$language['setting_2'];?>" onClick="window.location.href='my.setting.php?id=<?=$id;?>&act=foto'">
    </td><td>
   </td>
   </tr>
   
    <tr>
    <td class="ta_r">
     <span class="grey"><?=$language['setting_50'];?>:</span>
    </td>
    <td style="padding: 0px;">
		<input type="button" value="<?=$language['setting_7'];?> " onClick="window.location.href='my.setting.php?id=<?=$id;?>&act=passkey'">
		<br><small><?=$language['setting_6'];?> </small>
    </td><td>
   </td>
   </tr>
    

 </table> 
<?
end_frame();

if(( ($arr['class'] >= UC_MODERATOR && $USER['class'] == UC_DIRECTOR) || ($arr['class'] < UC_MODERATOR && $USER['class'] >= UC_MODERATOR)  ) && $arr['id'] != $USER['id']) {
	begin_frame($language['setting_10']);
	msg($language['setting_51']);
	?>
	<table width="80%" cellspacing="7" cellpadding="0" border="0" align="center">
	 <tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['setting_52'];?>:</span>
		</td>
		<td style="padding: 0px;">
			<? if(!$arr['banned']) { ?> 
			<input type="button" value="<?=$language['setting_53'];?>" onClick="window.location.href='my.setting.take.php?id=<?=$id;?>&act=ban_account'"> 
			<?  } else {?> 
			<input type="button" value="<?=$language['setting_54'];?>" onClick="window.location.href='my.setting.take.php?id=<?=$id;?>&act=ban_account'"> <? } ?>
			
			<br><small><?=$language['setting_55'];?> </small>
		</td><td>
	   </td>
	   </tr>
	   
	   
	   <tr>
		<td class="ta_r">
		 <span class="grey"><?=$language['setting_56'];?>:</span>
		</td>
		<td style="padding: 0px;">
			<?
			$db->query("SELECT * FROM bans  WHERE '".ip2long_db($arr['ip'])."'  >= first AND '".ip2long_db($arr['ip'])."' <= last");
			if(!$db->num_rows()) {
			?>
			<input type="button" value="<?=$language['setting_57'];?>" onClick="window.location.href='my.setting.take.php?id=<?=$id;?>&act=ban_ip'">
			<? } else { ?>
			<input type="button" value="<?=$language['setting_58'];?>" onClick="window.location.href='my.setting.take.php?id=<?=$id;?>&act=ban_ip'">
			<? } ?>
			<br><small><?=sprintf($language['setting_59'] , long2ip($arr['ip']));?></small>
		</td><td>
	   </td>
	   </tr>
	   


	 </table> 
	<?
	end_frame();
}
//Подвал
foot();

?>
