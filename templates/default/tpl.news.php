<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Новостной системы
////////////////////////////////////////////////////////

begin_frame($name);
?>
<a href="news.php?id=<?=$id;?>"><?=$name;?></a>
<hr>
<table width="100%">
<tr>
<td valign="top" width="50">
<a href="<?=profile_href($user_id);?>"><?=$avatar;?></a>
</td>
<td valign="top">
	<table cellspacing="3" cellpadding="0" class="profileTable" width="100%">
		
		 <tbody>	
		 <tr>
		 <td class="label" width="10%"><b><?=$language['news_14'];?></b>:</td>
		 <td class="data">
		  <div class="dataWrap">
			<?=$date;?>
		  </div>
		 </td>
		</tr>
		
		<tr>
		 <td class="label"><b><?=$language['news_15'];?></b>:</td>
		 <td class="data">
		  <div class="dataWrap">
			<a href="<?=profile_href($user_id);?>"><?=get_user_color($user_class , $user_name);?></a>
		  </div>
		 </td>
		</tr>
		
		

		
		<tr>
		 
		 <td class="data" colspan="2">
		  <div class="dataWrap">
		  <hr>
			<?=$text;?>
		  </div>
		 </td>
		</tr>
		

		 </tbody></table>

		<? if($PRIV['news_add']){ ?>
		<input type="button" value="<?=$language['news_16'];?>" onCLick="window.location.href='news.php?act=edit&id=<?=$id;?>'">&nbsp
		<input type="button" value="<?=$language['news_17'];?>" onCLick="window.location.href='news.php?act=delete&id=<?=$id;?>'">&nbsp
		<? } ?>
		<?=$field;?>
</td>
</tr>
</table>		
<?
end_frame();
?>

<?
if(defined('NEWS_DETAILS') ) {
	//Комментарии
	begin_frame($language['comments_13']);
	listComment('news' , $id , 'news.php?id='.$id.'&');
	end_frame();
}
?>


