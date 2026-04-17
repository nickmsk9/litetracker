<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Статистики
////////////////////////////////////////////////////////
//$name - Имя торрента
//$id - id торрента


if(!$infohash) {
	msg('Данный релиз невозможно скачать, так как у него нету торрента-файла');
}

begin_frame($name);	
?>
<table width="100%" cellpadding="3">
<tr>

<td width="250" valign="top">
<?


echo '<img src="'.$image.'" width="250">';


//Функции
// begin_frame($language['details_1']);

if($infohash) {
	echo ($PRIV['download_torrent'] ? '<a class="proleft" href="download.php?id='.$id.'">'.$language['details_2'].'</a>' : '');
	echo  ($PRIV['download_magnet'] ? '<a class="proleft" href="download.php?id='.$id.'&magnet=1">'.$language['details_3'].'</a>' : '');
}

if($PRIV['edit_release'] || $USER['id'] == $id_user) {
	echo  '<a class="proleft" href="edit.php?id='.$id.'">'.$language['details_23'].'</a>';
}
echo $book;

// end_frame();
?>
</td>


<td valign="top">

<?
//Описание

echo $descr;

// echo '<center><font size="4">Спасибо , что вы с нами !</font> <a href=\'http://www.toptracker.ru/details.php?id=8\' target="_blank" title=\'TopTracker.Ru - Рейтинг трекеров.\'><img src=\'http://www.toptracker.ru/buttons/counter.gif?id=8&style=4\'></a></center>';
// end_frame();

?>
</td>
</tr>

</table>


<?
end_frame();

//Характеристики
begin_frame($language['details_4']);

?>
<table cellspacing="7" cellpadding="0" class="profileTable" width="100%">

     <tbody>
	 
	<? if($config['vkontakte']['use']) { ?>
	 <tr>
     <td class="label">Понравилось?</td>
     <td class="data">
      <div class="dataWrap">
		<? echo vkontakte_like($name  , $descr, $id); //Виджет "Мне нравится" ?>
      </div>
     </td>
    </tr>
	<? } ?>

	 <? if($infohash) { ?>
	 <tr>
     <td class="label"><?=$language['details_5'];?>:</td>
     <td class="data">
      <div class="dataWrap">
       <noindex><a href="http://www.google.com/search?q=<?=$infohash;?>" target="_blank"><?=$infohash;?></a></noindex>
      </div>
     </td>
    </tr>
	
	
	<? } ?>

	<tr>
     <td class="label"><?=$language['details_6'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$date;?>
      </div>
     </td>
    </tr>
	
	<tr>
     <td class="label"><?=$language['details_7'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$banned;?>
      </div>
     </td>
    </tr>
	
	<tr>
     <td class="label"><?=$language['details_8'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <?=$size?> (Файлов : <?=number_format($arr['num_files']);?>) 
	    <? if($arr['type'] == 'multi') { ?>
			<br><small><a href="details.php?id=<?=$id;?>&files">Информация о файлах</a></small>
	<? } ?>
      </div>
     </td>
    </tr>
	
	<tr>
     <td class="label"><?=$language['details_9'];?>:</td>
     <td class="data">
      <div class="dataWrap">
      <img src="public/images/up.png"> <?=$language['details_10'];?> <?=$seeders;?> <img src="public/images/down.png"> <?=$language['details_11'];?> <?=$leechers;?>
	  <br>
	  <small><?=sprintf($language['details_30'] , $id);?></small> | <?=($multi ? '<small>'.sprintf($language['details_12'] , $id).'</small>' : '<small>'.$language['details_13'].'</small>');?>
      </div>
     </td>
    </tr>
	
	<tr>
     <td class="label"><?=$language['details_14'];?>:</td>
     <td class="data">
      <div class="dataWrap">
		<?=$tags;?>
      </div>
     </td>
    </tr>
	
	<tr>
     <td class="label"><?=$language['details_28'];?>:</td>
     <td class="data">
      <div class="dataWrap">
		<a href="browse.php?id_category=<?=$cat_id;?>"><?=$cat_name;?></a>
      </div>
     </td>
    </tr>
	
	
	
	<tr>
     <td class="label"><?=$language['details_15'];?>:</td>
     <td class="data">
      <div class="dataWrap">
		<a href="<?=profile_href($id_user);?>"><?=get_user_color($user_class, $user_name);?></a>
      </div>
     </td>
    </tr>
	

     </tbody></table>
<?
end_frame();

//Скриншоты
require 'modules/screens.php';


//Видео ВКонтакте
if($video_vkontakte) {
	//Видео ВКонтакте
	begin_frame($language['details_17']);
	echo $video_vkontakte;
	end_frame();
}

//Комментарии
begin_frame($language['details_18']);
listComment('torrents' , $id , 'details.php?');
end_frame();
?>
