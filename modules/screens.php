<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Скриншоты(для главной и для деталей)
===================================================================
*/
//Необходимые параметры
//$arr['screen_{i} - массив cо скриншотами
//$id - ID релиза

?>
<script type="text/javascript" src="/public/js/jquery.lightbox.js"></script>
<link rel="stylesheet" type="text/css" href="/public/css/lightbox.css" />
<?
$i = 0;
$screens = array();
for(;$i <= 4 ;$i++ ) {
	if($arr['screen_'.$i] != '') {
	if(is_file('public/downloads/screens/'.$arr['screen_'.$i]) ) {
		$file = 'public/downloads/screens/'.$arr['screen_'.$i];
	}else {
		$file = htmlspeialchars($arr['screen_'.$i]);
	}
	  $screens[] =  ' <li class="gallery"> <a rel="lightbox-tour" href="'.$file.'" title="Скриншот №'.$i.'"><img src="'.$file.'" alt="" width="200" height="150" /></a></li>';  
	}
}

if(count($screens) ) {
	begin_frame('Скриншоты');
	echo '<ul id="gallery_'.$id.'" class="gallery">';
	echo implode("\n" , $screens);
	echo '</ul>';
	end_frame();
	echo '
	<script type="text/javascript">
	
		$("#gallery_'.$id.' a").lightbox();
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
}
?>