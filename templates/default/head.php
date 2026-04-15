<?php
if (!defined('LITETRACKER'))
die('Direct access denied.');
?> 
<html><head>
<!--Мета теги-->
<link href="templates/<?=$tpl;?>/css/my.css" rel="StyleSheet" type="text/css">
<!--/CSS-->


<!--Мета теги-->
 
<!--CSS-->
<link href="templates/<?=$tpl;?>/css/my.css" rel="StyleSheet" type="text/css">
<link href="templates/<?=$tpl;?>/css/buttons.css" rel="StyleSheet" type="text/css">
<!--/CSS-->
 

<!--Скрипты-->
<?=$header;?>

<!--/Скрипты-->
 
<!--Админ бар-->
<style></style>
<!--/Админ бар-->

<!--Левое меню-->
<div><!--U1CLEFTER1Z--><!--block123-->

<div style="padding-top: 10px;">
<div align="center">
 <div id="middleC">
 <div id="leftBlockContainer">
 <div id="leftBlock">
 <div class="nborder4">
 <div class="nborder5">
 <div style="padding-bottom: 3px; padding-top: 3px;" class="nborder6">
<div>
 
 <div style="padding: 1px 0px 1px 2px;" id="menuleft">
 

 <table width="170px" cellspacing="0" cellpadding="1"><tbody><tr><td width="50px" valign="top" class="avatar"><?=($USER ? '<a title="Перейти к моей странице" href="profile.php?id='.$USER['id'].'">'.($USER['avatar'] ? '<img src="public/avatars/small/'.$USER['avatar'].'">' : '<img src="public/images/default_avatar.gif">').'</a>' : '<img src="public/images/default_avatar.gif">')?></td><td valign="top" align="left" style="padding-left: 5px;">
 <div><b><?=($USER ? '<a title="Перейти к моей странице" href="profile.php?id='.$USER['id'].'">'.get_user_color($USER['class'] , $USER['name']).'</a>' : 'Гость')?></b></div>
 <div style="color: rgb(102, 102, 102);"><small>Добро пожаловать</small></div>
 <?
 if($USER) 
 {
 ?>
 <div>
 <table cellspacing="4" cellpadding="4" align="center">
 <tbody><tr>
 <td>
 <a title="Личные сообщения" class="addmenu" href="my.mail.php">
 <img width="16" height="16" src="templates/<?=$tpl;?>/images/mymail.gif"></a>
 </td>
 
 <td>
 <a title="Настроить профиль" class="addmenu" href="my.setting.php">
 <img width="16" height="16" src="templates/<?=$tpl;?>/images/setting.gif"></a>
 </td> 
 </tr>

</tbody></table>


 </div>
 <?
 }
 ?>
 </td></tr>
 <tr>
 <td colspan="2">
 <? if($USER) { ?>
 <table cellspacing="4" cellpadding="4" align="left">
 <tr>
	 <td>
		<img src="public/images/up.png" title="Раздал"> <?=mksize($USER['uploaded']);?>
	 </td>

	 <td>
		<img src="public/images/down.png" title="Скачал"> <?=mksize($USER['downloaded']);?>
	 </td>
 </tr>
 
  <tr>
	 <td colspan="2">
		<img src="public/images/dollar.png" title="Скачал"> <?=$USER['voice'];?> y.e
	 </td>
 </tr>
 </table>
  <? } ?>
 </td>
 </tr>
 </tbody></table>

 
 <hr>
 
 <a title="Добавить контент" onclick="$('#addblock').slideToggle('fast');" href="javascript:void(0)">
 <div valign="center" class="menuzag">
 <span valign="center">Загрузить ...</span>
 </div>
 </a> 
 <div style="display: none;" id="addblock" class="menubody"> 
 <table cellspacing="2" cellpadding="1" align="center">
 <tbody>
 
 
 <tr>
 <?
 $cache_result = categories_array();
	$categories = '';
	$count = 0;
	foreach ($cache_result AS $cat) {	
		$count++;
		if($count == 0) echo '<tr>'; 
		echo ' <td>
			 <a title="'.htmlspecialchars($cat['name']).'" class="addmenu" href="upload.php?catid='.$cat['id'].'&act=next">
			 <img width="16" height="16" src="public/images/categories/'.$cat['image'].'"></a>
			 </td>';
			 
		if($count == 3) { 
			$count = 0 ; 
			echo '</tr>';
		} 
	}

?>
 
 </td></tr>
 <tr><td colspan="3">
	 <a class="addmenu" href="upload.php">Выбрать другую категорию...</a>
</td></tr>	 
 </tbody></table>
</div>
<hr>
<a class="proleft" href="browse.php?act=all">
 Торренты
</a>
<a class="proleft" href="users.php">
 Люди
</a>
 
 <a class="proleft" href="news.php">
 Новости
 </a>
 
 <a class="proleft" href="faq.php">
 FAQ
</a>

 <a class="proleft" href="shop.php">
 Магазин
</a>
 
 <hr>
 
 <?
	// $cache_result = categories_array();
	foreach ($cache_result AS $cat) {
		$categories .=  '<a class="proleft" href="browse.php?id_category='.$cat['id'].'">'.htmlspecialchars($cat['name']).'</a>';
	}
	echo $categories;
 ?>	

 <?
 if($USER)
 {
 ?>
 <hr>
 <a class="proleft" href="exit.php">
 Выход
 </a>
 
 
 <?=($PRIV['search_query'] ? '<a class="proleft" href=\'search_query.php\' title=\''.$language['template_12'].'\'>'.$language['template_12'].'</a>' : '');?>
	<?=($PRIV['multitracker_accounts'] ? '<a class="proleft" href=\'multitracker_accounts.php\' title=\''.$language['template_13'].'\'>'.$language['template_13'].'</a>' : '');?>
	<?=($PRIV['ip_util'] ? '<a href=\'ip.util.php\' class="proleft" title=\''.$language['template_14'].'\'>'.$language['template_14'].'</a>' : '');?>
	<?=($PRIV['sessions_view'] ? '<a class="proleft" href=\'sessions.php\' title=\''.$language['template_15'].'\'>'.$language['template_15'].'</a>' : '');?>
	<?=($PRIV['cats'] ? '<a class="proleft" href=\'categories.php\' title=\''.$language['template_16'].'\'>'.$language['template_16'].'</a>' : '');?>
	<?=($PRIV['messages'] ? '<a class="proleft"  href=\'messages.php\' title=\''.$language['template_27'].'\'>'.$language['template_27'].'</a>' : '');?>
	<?=($PRIV['news_add'] ? '<a class="proleft" href=\'news.php?act=add\' title=\''.$language['template_17'].'\'>'.$language['template_17'].'</a>' : '');?>
	<?=($PRIV['user_add'] ? '<a class="proleft" href=\'user_add.php\' title=\''.$language['template_29'].'\'>'.$language['template_29'].'</a>' : '');?>
	<?=($PRIV['faq_moderate'] ? '<a class="proleft" href=\'faq.php?act=topic&type=add\' title=\'Добавить FAQ\'>Добавить FAQ</a>' : '');?>
	<?=($PRIV['EDIT_PRIV'] ? '<a class="proleft" href=\'edit_priv.php\' title=\''.$language['template_28'].'\'>'.$language['template_28'].'</a>' : '');?>
	<?=($PRIV['EDIT_PRIV'] ? '<a  class="proleft" href=\'blocks.php\' title=\'Блоки\'>Блоки</a></li>' : '');?>
	<?=($PRIV['polls_moderate'] ? '<a  class="proleft" href=\'polls.php\' title=\'Блоки\'>Опросы</a></li>' : '');?>
 

 <?
 }
 ?>

 <hr>
 
 <style>&lt;div style="padding-bottom:5px"&gt;&lt;a href="javascript:void(0)" onclick="$('#forum').slideToggle('fast');"&gt;
 &lt;div class="menuzag" valign="center"&gt;
 &lt;span valign="center"&gt;Форум&lt;/span&gt;
 &lt;/div&gt;
 &lt;/a&gt;
 &lt;div id="forum" class="menubody" style="display:none;"&gt;&lt;table border="0" cellpadding="1" cellspacing="0" width="100%" id="proFile"&gt; &lt;tr&gt;&lt;td&gt;&lt;a href="/forum/4-21-1" class="proava" title="Перейти в тему: Bloggi - Плагиат"&gt;Bloggi - Плагиат&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt; &lt;/table&gt;&lt;table border="0" cellpadding="1" cellspacing="0" width="100%" id="proFile"&gt; &lt;tr&gt;&lt;td&gt;&lt;a href="/forum/4-50-1" class="proava" title="Перейти в тему: Зачем вообще uBloggi..."&gt;Зачем вообще uBloggi...&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt; &lt;/table&gt;&lt;table border="0" cellpadding="1" cellspacing="0" width="100%" id="proFile"&gt; &lt;tr&gt;&lt;td&gt;&lt;a href="/forum/9-51-1" class="proava" title="Перейти в тему: Конкурс подарков"&gt;Конкурс подарков&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt; &lt;/table&gt;&lt;table border="0" cellpadding="1" cellspacing="0" width="100%" id="proFile"&gt; &lt;tr&gt;&lt;td&gt;&lt;a href="/forum/4-52-1" class="proava" title="Перейти в тему: Кто что взял с этого..."&gt;Кто что взял с этого...&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt; &lt;/table&gt;&lt;table border="0" cellpadding="1" cellspacing="0" width="100%" id="proFile"&gt; &lt;tr&gt;&lt;td&gt;&lt;a href="/forum/9-34-1" class="proava" title="Перейти в тему: Сервер Ubloggi по Co..."&gt;Сервер Ubloggi по Co...&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt; &lt;/table&gt;&lt;table border="0" cellpadding="1" cellspacing="0" width="100%" id="proFile"&gt; &lt;tr&gt;&lt;td&gt;&lt;a href="/forum/4-18-1" class="proava" title="Перейти в тему: Bloggi - Реклама"&gt;Bloggi - Реклама&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt; &lt;/table&gt;&lt;table border="0" cellpadding="1" cellspacing="0" width="100%" id="proFile"&gt; &lt;tr&gt;&lt;td&gt;&lt;a href="/forum/10-17-1" class="proava" title="Перейти в тему: Подарки"&gt;Подарки&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt; &lt;/table&gt;&lt;table border="0" cellpadding="1" cellspacing="0" width="100%" id="proFile"&gt; &lt;tr&gt;&lt;td&gt;&lt;a href="/forum/9-39-1" class="proava" title="Перейти в тему: Сеть секс знакомств"&gt;Сеть секс знакомств&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt; &lt;/table&gt;&lt;table border="0" cellpadding="1" cellspacing="0" width="100%" id="proFile"&gt; &lt;tr&gt;&lt;td&gt;&lt;a href="/forum/9-4-1" class="proava" title="Перейти в тему: Общение"&gt;Общение&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt; &lt;/table&gt;&lt;table border="0" cellpadding="1" cellspacing="0" width="100%" id="proFile"&gt; &lt;tr&gt;&lt;td&gt;&lt;a href="/forum/5-29-1" class="proava" title="Перейти в тему: стенка (Записки)"&gt;стенка (Записки)&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt; &lt;/table&gt;&lt;/div&gt;
 &lt;/div&gt;</style>




 
 </div>
 
 <? show_blocks('l'); ?>
 
</div></div></div></div>
 


</div>
<!--/U1CLEFTER1Z--></div>
<!--/Левое меню-->
 

 
<!--Рекламный блок-->

<!--/Рекламный блок-->


<div id="centerBlock">
<div id="centerBlockContainer">
<div class="nborder1">
<div class="nborder2">
<div class="nborder3">
 <!--U1AHEADER1Z--><div class="bloggi2">
 <div class="bloggi">
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tbody><tr>
 <td>

 <table cellspacing="0" cellpadding="2" border="0">
 <tbody><tr>
 
 
 <!--
 <td><div><a class="logos" href="index.php">
<img width="27" height="20" border="0" alt="" src="templates/<?=$tpl;?>/images/ubllogo1.png">
</a>
 
 </div>
</td>-->
 
 <?
 if($USER)
 {
 ?>
 
  <td><div><a class="mymemuup" href="index.php">
 <font color="white">
  Главная 
 </font>
 </a></div></td>
 
 
 <td><div><a class="mymemuup" href="my.friends.php">
 <font color="white">
  Друзья <?=($USER['num_friends'] ? '<b>('.$USER['num_friends'].')</b>' : '');?> 
 </font>
 </a></div></td>
 <td><div><a class="mymemuup" href="my.mail.php">
 <font color="white">
	Cообщения <?=($USER['num_messages'] ? '<b>('.$USER['num_messages'].')</b>' : '');?> 
 </font>
 </a></div></td>

 <td><div><a class="mymemuup" href="my.book.php"><font color="white">
 Закладки
 </font></a></div></td>
 
 <td><div><a class="mymemuup" href="my.blocks.php"><font color="white">
 Блоки
 </font></a></div></td>
 <td><div><a class="mymemuup" href="my.releases.php"><font color="white">
 Торренты
 </font></a></div></td>
 <td><div><a class="mymemuup" href="my.setting.php"><font color="white">
 Настройки
 </font></a></div></td>
 
  <td><div><a class="mymemuup" href="exit.php"><font color="white">
 Выход
 </font></a></div></td>
 <?
 }
 else
 {
 ?>
 <td><div><a class="mymemuup" href="login.php">
 <font color="white">
  Вход
 </font>
 </a></div></td>
 <td><div><a class="mymemuup" href="signup.php">
 <font color="white">
  Регистрация
 </font>
 </a></div></td>
 
 <td><div><a class="mymemuup" href="login.php?op=forgot">
 <font color="white">
  Забыли пароль?
 </font>
 </a></div></td>

 <?
 }
 ?>
 </tr>
 </tbody></table>
 
 
</td>
 
 
 </tr></tbody></table>
 </div></div>
 
<div style="padding-top: 7px; padding-right: 15px;">
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tbody><tr>
 
 
</tr>
</tbody></table>
</div><!--/U1AHEADER1Z-->
 <div class="blockContent">
 <style>.blM34 {display: none;}
 </style>
 

<?
// Плохой рейтинг
if(!empty($USER['bad_rating']) && !empty($PRIV['bad_rating'])) {
	begin_frame();
	msg($language['template_6']);
	end_frame();
}
?>
 
<? show_blocks('c'); ?>

