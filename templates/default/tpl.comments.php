<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Комментарий
////////////////////////////////////////////////////////
//$id - id пользовтаеля

begin_frame();
?>

<a href="<?=$rewrite->encode('profile.php?id='.$user_id);?>"><?=get_user_color($user_class ,$user_name);?></a> написал<br>
			<?=($append_edit ? '<small>'.$append_edit.'</small>' : '<small>'.$date.'</small>');?>
			<hr>

        <table>
          
          <tbody>
              <tr>
              
              <td><a href="<?=$rewrite->encode('profile.php?id='.$user_id);?>"><?=$avatar;?></a></td>
             
              <td  valign="top"><?=$text;?>
				<hr>
				 <? if($USER['id'] == $user_id || $PRIV['comments_edit']) { ?><input type="button" value="<?=$language['comments_4'];?>" onClick="window.location.href='comments.take.php?type=<?=$type;?>&object_id=<?=$object_id;?>&id_comment=<?=$id;?>&act=edit&file=<?=$file;?>'"><? } ?>
				 <? if($USER['id'] == $user_id || $PRIV['comments_delete']) { ?><input type="button" value="<?=$language['comments_5'];?>" onClick="window.location.href='comments.take.php?type=<?=$type;?>&object_id=<?=$object_id;?>&id_comment=<?=$id;?>&act=delete&file=<?=$file;?>'"><? } ?>
			
			
			  </td>
           
            </tr>
                </tbody>
        </table>
      
<? end_frame();?>
<!--
<table width="70%" cellpadding="2" style="border:1px">
<tr>
<td valign="top" width="80"><a href="<?=$rewrite->encode('profile.php?id='.$user_id);?>"><?=$avatar;?></a></td>
<td  valign="top" >



	<table cellspacing="7" cellpadding="0" class="profileTable" width="100%">

		 <tbody>	
		 <tr>
		
		 <td class="data">
		  <div class="dataWrap">
			<a href="<?=$rewrite->encode('profile.php?id='.$user_id);?>"><?=get_user_color($user_class ,$user_name);?></a><br>
			<?=($append_edit ? '<small>'.$append_edit.'</small>' : '<small>'.$date.'</small>');?>
		  </div>
		 </td>
		</tr>
		
		
		<tr>
		 <td class="data">
			<div class="dataWrap">
			<?=$text;?>
			
			</div>
		 </td>
		</tr>
		
		<tr>
		 <td class="data">
			<div class="dataWrap">
			<? if($USER['id'] == $user_id || $USER['class']  >= UC_MODERATOR) { ?>
				<input type="button" value="<?=$language['comments_4'];?>" onClick="window.location.href='comments.take.php?type=<?=$type;?>&object_id=<?=$object_id;?>&id_comment=<?=$id;?>&act=edit&file=<?=$file;?>'">
				<input type="button" value="<?=$language['comments_5'];?>" onClick="window.location.href='comments.take.php?type=<?=$type;?>&object_id=<?=$object_id;?>&id_comment=<?=$id;?>&act=delete&file=<?=$file;?>'">
			<? } ?>
			
			</div>
		 </td>
		</tr>
		
		
		 </tbody></table>
</td>

</tr>
</table>-->