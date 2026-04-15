<?php
$search = trim((string) ($_GET['search'] ?? ''));
$type = (string) ($_GET['type'] ?? 'name');
$id_category = (string) ($_GET['id_category'] ?? '');
$sort = (string) ($_GET['sort'] ?? '');
$cats = '';
?>
<form action="browse.php" method="GET">
<?php
begin_frame($language['search_1']);

$categories_array = categories_array();
foreach ($categories_array as $thisCat) {
	$cats .= '<option value="'.$thisCat['id'].'" '.($id_category == $thisCat['id'] ? 'selected' : '').'>'.$thisCat['name'].'</option>';
}
?>
	<div class="search-form">
		<div class="search-row search-row-primary">
			<input type="text" name="search" class="inputText search-input" onkeypress="checkResult();" autocomplete="off" value="<?=htmlspecialchars($search, ENT_QUOTES, 'UTF-8');?>" placeholder="<?=$language['search_1'];?>">
			<input type="submit" value="<?=$language['search_1'];?>">
		</div>

		<div class="search-row search-row-filters">
			<select name="type" class="search-select">
				<option value="name" <?=($type == 'name' ? 'selected' : '');?>><?=$language['search_2'];?></option>
				<option value="tags" <?=($type == 'tags' ? 'selected' : '');?>><?=$language['search_3'];?></option>
			</select>

			<select name="id_category" class="search-select">
				<option value="" <?=($id_category == '' ? 'selected' : '');?>>(<?=$language['search_4'];?>)</option>
				<?=$cats;?>
			</select>

			<select name="sort" class="search-select">
				<option value="" <?=($sort == '' ? 'selected' : '');?>>(<?=$language['search_5'];?>)</option>
				<option value="desc" <?=($sort == 'desc' ? 'selected' : '');?>><?=$language['search_6'];?></option>
				<option value="asc" <?=($sort == 'asc' ? 'selected' : '');?>><?=$language['search_7'];?></option>
				<option value="rand" <?=($sort == 'rand' ? 'selected' : '');?>><?=$language['search_8'];?></option>
			</select>
		</div>
	</div>
<?php
end_frame();
?>
</form>
