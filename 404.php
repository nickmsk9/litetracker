<?php
### security protection by n-sw-bit ::: 404 ###
$H=getenv("REQUEST_URI");
if(strpos($_SERVER['REQUEST_URI'],"admin")!==false){
	include("system/init.php");
	
	hacker("404 {".$_SERVER['REQUEST_URI']."}");
	
}else{
	require "./system/init.php";
}
header("HTTP/1.0 404 Not Found"); 
head("Страница не найдена!");
begin_frame();
?>
<base href="<?=$DEFAULTBASEURL?>" />

<center><h1>Ошибка 404 !</h1>
<div style="font-weight:bold;display:block;padding:10px;margin:10px;font-size:15px;">К сожалению, запрошенная Вами страница не найдена.
</div>
<p style="text-align: center;"><font color="black"><font>•</font><font>-------.<br/>.----------------<br/>.--------------------<br/>.------------------------<br/>----------------------------<br/>------------------------------<br/>-------------------------------<br/>--------------------------------<br/>--------------------------------<br/>---------------------------------<br/>---------------------------------<br/>---------------------------------<br/>-------------------------------<br/>------------------------------<br/>----------------------------<br/>--------------------------<br/>-------------------------<br/>------------------------<br/>----------------------<br/>-------------------<br/>--------------<br/>------------<br/>---------------<br/>---------------<br/>.,--------------------<br/>----------------------------<br/>----------------------------<br/>-----------------------------<br/>---------------------------<br/>-------------------------</font></font></p>
<div style="font-weight:bold;display:block;padding:10px;margin:10px;font-size:15px;"><a href="javascript:history.go(-1);" style="color:blue;text-decoration:none;">Назад</a></div>
</center>
<?
end_frame();
foot();

?>