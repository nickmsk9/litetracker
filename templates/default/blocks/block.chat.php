<?
if (!defined('LITETRACKER'))
die('Direct access denied.');


////////////////////////////////////////////////////////
//Шаблон для Чата
////////////////////////////////////////////////////////
//$param - Описание

begin_frame('Комната для общения');
?>

	

<form name="chat" action="javascript:void(0)" onsubmit="send();">
	<table width="100%">
		<tbody>
					<? if($USER) { ?>
			<tr>
				<td class="row1"><center><input type="text"  name="text_chat" id="text_chat" style="width:80%"> <input type="button" value="<?=$language['chat_3'];?>" onCLick="send();"></center></td>
			</tr>
			<? } ?>
			<tr>
				<td align="left" class="row1">

					<div id="result_chat" style="overflow: auto; height: 250px; width:100%;  border: solid #dae1e8 0px;">
					<br><center><img src="public/images/loading.gif" alt="<?=$language['default_4'];?>"></center>
					</div>	

					<table width="100%" cellspacing="1" cellpadding="1" border="0">
						<tbody>
						<tr>
							<td width="70%" align="right" class="row2">
								<small>
								<?=($PRIV['chat_clear'] ? '<a title="'.$language['chat_2'].'" onClick="confirm_clear();" href="javascript:void(0)">'.$language['chat_2'].'</a>' : '');?>
								</small>
							</td>
						</tr>

						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<td class="row1"><div id="result_send" class="row1"></div></tr>
			</tr>
		</tbody>
	</table>
</form>
<?
end_frame();
?>



