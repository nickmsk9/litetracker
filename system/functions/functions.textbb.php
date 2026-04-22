<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Редактор
===================================================================
*/

//Вывод формы
function textbb($name, $text = '' , $width='95%' , $height = '300px') {
?>
	<textarea id="<?=$name;?>" name="<?=$name;?>" style="width:<?=$width?>;height:<?=$height;?>"><?=htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');?></textarea>
	<?php
}

//Обработка данных
/**
 * Cleans html code using HTMLawed
 * @param string $code Text to be processed
 * @return string The cleaned html code
 */
function cleanhtml($code) {
	return format_comment($code );

}

?>
