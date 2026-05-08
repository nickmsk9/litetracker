<?
if (!defined('LITETRACKER'))
	die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для вывода Друзей
////////////////////////////////////////////////////////
//$id - id пользовтаеля
?>
<!--
<table>
	<tr>
		<td width="100" align="center"><a href="<?=profile_href($userid);?>"><?=$avatar;?></a></td>
		<td valign="top"><font size="3"><a href="<?=profile_href($userid);?>"><?=$name;?></a></font> 
		<br> <?=$date;?> <br>
		<?=$action;?></td>
	</tr>
</table>
-->
<?
begin_frame();
?>
<table width="70%" cellpadding="3" style="border:1px">
<tr>
<td valign="top" width="50" align="center">
<a href="<?=profile_href($userid);?>"><?=$avatar;?></a>
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
				<?=$action;?>
				<?
				echo ($USER['id'] != $userid ? '<a class="btn btn-secondary lt-btn lt-btn-secondary" href="my.mail.php?act=conversation&amp;id_user='.(int)$userid.'">'.htmlspecialchars($language['profile_17'], ENT_QUOTES, 'UTF-8').'</a>&nbsp;' : '');


				?>
			
		 
		 </td>
		</tr>
		

		 </tbody></table>
</td>

</tr>
</table>
<? end_frame();?>
