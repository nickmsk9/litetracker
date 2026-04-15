<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Редактор
===================================================================
*/

//Вывод формы
function textbb($name, $text = '' , $width='95%' , $height = '300px') {
?>

	<!-- markItUp! -->
	<script type="text/javascript" src="public/markitup/jquery.markitup.pack.js"></script>
	<!-- markItUp! toolbar settings -->
	<script type="text/javascript" src="public/markitup/sets/bbcode/set.js"></script>
	<!-- markItUp! skin -->
	<link rel="stylesheet" type="text/css" href="public/markitup/skins/simple/style.css" />
	<!--  markItUp! toolbar skin -->
	<link rel="stylesheet" type="text/css" href="public/markitup/sets/bbcode/style.css" />
	
	<script type="text/javascript">
	<!--
	$(document).ready(function()	{
		// Add markItUp! to your textarea in one line
		// $('textarea').markItUp( { Settings }, { OptionalExtraSettings } );
		$('#<?=$name;?>').markItUp(mySettings);
		
		// You can add content from anywhere in your page
		// $.markItUp( { Settings } );	
		$('.add').click(function() {
			$.markItUp( { 	openWith:'<opening tag>',
							closeWith:'<\/closing tag>',
							placeHolder:"New content"
						}
					);
			return false;
		});
		
		// And you can add/remove markItUp! whenever you want
		// $(textarea).markItUpRemove();
		$('.toggle').click(function() {
			if ($("#<?=$name;?>.markItUpEditor").length === 1) {
				$("#<?=$name;?>").markItUpRemove();
				$("span", this).text("get markItUp! back");
			} else {
				$('#<?=$name;?>').markItUp(mySettings);
				$("span", this).text("remove markItUp!");
			}
			return false;
		});
	});
	-->
	</script>
	
	<textarea id="<?=$name;?>" name="<?=$name;?>"  style="width:<?=$width?>;height:<?=$height;?>" ><?=$text;?></textarea>
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
