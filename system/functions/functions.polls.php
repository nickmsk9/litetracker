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

//Вывод опроса
function echo_poll() {
	global $config , $db , $USER;
	if(!$USER) { 
		return;
	}
	
	begin_frame('Опрос');
	?>
	<!--Подключаем сторонние файлы-->
	<link media="screen" href="public/css/polls.css" type="text/css" rel="stylesheet" />
	<script type="text/javascript" src="public/js/polls.js"></script>
    <script type="text/javascript">$(document).ready(function(){loadpoll();});</script>

	<!--Выводим таблицу-->
	<table width="100%" border="0" cellspacing="0" cellpadding="10" align="center">
		 <tr>
		  <td align="center">
			<div id="poll_container">
				<div id="loading_poll" style="display:none"></div>
				<noscript>
				<b>Активируйте JavaScript</b>
				</noscript>
			</div>
			<br/>
		  </td>
		 </tr>
	</table>
		<?php
	end_frame();
}
?>
