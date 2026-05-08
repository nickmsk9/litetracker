<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Пользователи
////////////////////////////////////////////////////////
//$id - id пользовтаеля

begin_frame();
?>
<table width="70%" cellpadding="3" style="border:1px">
<tr>
<td valign="top" width="50">
<a href="<?=profile_href($id);?>"><?=$avatar;?></a>
</td>
<td  valign="top" >



	<table cellspacing="3" cellpadding="0" class="profileTable table-clean" width="100%">

		 <tbody>

	
		 
		 <tr>
		 <td class="label" width="15%"><b><?=$language['users_16'];?></b></td>
		 <td class="data">
		  <div class="dataWrap" style="float:left">
			<a href="<?=profile_href($id);?>"><?=$name;?></a> <?=$online;?>
		  </div>
		  
		 
		 </td>
		
		</tr>
		
		
		<tr>
		 <td class="label"><b><?=$language['users_17'];?></b></td>
		 <td class="data">
		  <div class="dataWrap">
			<?=$date;?>
		  </div>
		 </td>
		</tr>
		
		<tr>
		 <td class="label"><b><?=$language['users_18'];?></b></td>
		 <td class="data">
		  <div class="dataWrap">
			<?=$class;?>
		  </div>
		 </td>
		</tr>
		
		
		
		<tr>
		 
		 <td class="data" colspan="2">
				<?
				echo ($USER['id'] == $arr['id'] || !empty($PRIV['setting_user']) || !empty($PRIV['EDIT_PRIV']) ? '<a class="btn btn-secondary" href="my.setting.php?id='.$id.'">'.htmlspecialchars($language['profile_16'], ENT_QUOTES, 'UTF-8').'</a>&nbsp;' : '');
				echo ($USER['id'] != $arr['id'] ? '<a class="btn btn-secondary" href="my.mail.php?act=conversation&id_user='.$arr['id'].'">'.htmlspecialchars($language['profile_17'], ENT_QUOTES, 'UTF-8').'</a>&nbsp;' : '');


				?>
			
		 
		 </td>
		</tr>
		

		 </tbody></table>
</td>

</tr>
</table>
<? end_frame(); ?>
