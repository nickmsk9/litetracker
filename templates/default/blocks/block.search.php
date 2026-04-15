<form action="browse.php" method="GET">
<?
////////////////////////////////////////////////////////
//Шаблон для Поиск
////////////////////////////////////////////////////////
//$param - Описание

begin_frame($language['search_1']);

$search = trim((string) ($_GET['search'] ?? ''));
$type = (string) ($_GET['type'] ?? 'name');
$id_category = (string) ($_GET['id_category'] ?? '');
$sort = (string) ($_GET['sort'] ?? '');
$cats = '';
?>

<table width="100%" align="center"> 
	<tr>
	
	<td>
	
	<input type="text" name="search" size="90%" class="search"  onkeypress="checkResult();" autocomplete="off" value="<?=htmlspecialchars($search, ENT_QUOTES, 'UTF-8');?>">
	<input type="submit" value="<?=$language['search_1'];?>" class="search">&nbsp	
	</td>
	</tr>
	
	
	<tr>
	<td>
	
	<select name="type"   class="search">
		<option value="name" <?=($type == 'name' ? 'selected' : '');?> ><?=$language['search_2'];?></option>
		<option value="tags" <?=($type == 'tags' ? 'selected' : '');?>><?=$language['search_3'];?></option>
	</select>
	
	<?
	$categories_array =  categories_array();
	foreach($categories_array AS $thisCat)
		$cats .= '<option value="'.$thisCat['id'].'" '.($id_category == $thisCat['id'] ? "selected" : "").'>'.$thisCat['name'].'</option>';
	?>
	<select name="id_category"   class="search">
		<option value="" <?=($id_category == '' ? 'selected' : '');?> >(<?=$language['search_4'];?>)</option>
		<?=$cats;?>
	</select>
	
	<select name="sort"   class="search">
		<option value="" <?=($sort == '' ? 'selected' : '');?> >(<?=$language['search_5'];?>)</option>
		<option value="desc" <?=($sort == 'desc' ? 'selected' : '');?> ><?=$language['search_6'];?></option>
		<option value="asc" <?=($sort == 'asc' ? 'selected' : '');?> ><?=$language['search_7'];?></option>
		<option value="rand" <?=($sort == 'rand' ? 'selected' : '');?> ><?=$language['search_8'];?></option>
	</select>
	
	</td>
	</tr>

</table>
<?
end_frame();
?>
</form>


