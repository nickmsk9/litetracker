<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Пользователи
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверяем пользователя
is_login();

if(!$PRIV['users_view']) {
	err($language['default_1']  , $language['users_19'] , 1);
}

$where = array();
$get  =  array();

//Поисковая фраза
$search = trim($_GET['search']);
if(!empty($search) ) {
	$get[] = 'search='.$search;
	$where[] = "name LIKE '%" . sqlwildcardesc($search) . "%'";

}


//Сортировка
$sort = trim($_GET['sort']);
if($sort == 'desc' || empty($sort) ) {
	$get[] = 'sort=desc';
	$sort = 'ORDER BY added DESC';
}
elseif($sort == 'asc') {
	$get[] = 'sort=asc';
	$sort = 'ORDER BY added ASC';
}
elseif($sort == 'rand') {
	$get[] = 'sort=rand';
	$sort = 'ORDER BY RAND()';
} else {
	$get[] = 'sort=desc';
	$sort = 'ORDER BY added DESC';
}

//Класс
$class = (int)$_GET['class'];

if($class) {
	$get[] = 'class='.$class;
	$where[] = "class='".$class."'";
}


//Постраничная навигация
$db->query("SELECT *
			FROM users ".(count($where) ? 'WHERE '.implode(' AND ' , $where) : '')."");
$count_users = $db->num_rows();
list($pagertop, $pagerbottom, $limit) = pager('10', $count_users, 'users.php?'.(count($get) ? implode('&' ,$get).'&' : '') );


//Запрос
$sql = $db->query("SELECT * FROM users
			".(count($where) ? 'WHERE '.implode(' AND ' , $where) : '')."
		    ".$sort."
			".$limit."");



//Заголовок
head($language['users_1']);

begin_frame($language['users_2']);
?>
<form action="users.php" method="GET">
<table width="95%" align="center">
	<tr>
	<td>
	<input type="text" name="search" size="70%" class="search"   autocomplete="off" value="<?=htmlspecialchars($search);?>">
	<input type="submit" value="Поиск" class="search">
	</td>
	</tr>


	<tr>
	<td>


	<select name="class"   class="search">
		<option <?=($_GET['class'] == '' ? 'selected' : '');?>  value="">(<?=$language['users_3'];?>)</option>
		<?
		$classes = get_classes_list();
		foreach($classes AS $class) {
			echo '<option '.($class['id'] == $_GET['class'] ? 'selected' : '').' value="'.$class['id'].'">'.htmlspecialchars($class['NAME']).'</option>';
		}
		?>
	</select>

	<select name="sort"   class="search">
		<option value="" <?=($_GET['sort'] == '' ? 'selected' : '');?> >(<?=$language['users_10'];?>)</option>
		<option value="desc" <?=($_GET['sort'] == 'desc' ? 'selected' : '');?> ><?=$language['users_11'];?></option>
		<option value="asc" <?=($_GET['sort'] == 'asc' ? 'selected' : '');?> ><?=$language['users_12'];?></option>
		<option value="rand" <?=($_GET['sort'] == 'rand' ? 'selected' : '');?> ><?=$language['users_13'];?></option>
	</select>
	</td>
	</tr>



</table>
</form>
<?
end_frame();

begin_frame($language['users_1']);

if(!$db->num_rows($sql)) {
	msg($language['default_8'] , $language['users_14']);
} else {
	echo $pagertop;
	while($arr = $db->get_row($sql) ) {

		//Номер пользователя
		$id = $arr['id'];

		//Аватар
		$avatar = ($arr['avatar'] ? '<img src="public/avatars/'.$arr['avatar'].'" width="50">' : '<center><img src="public/images/default_avatar.gif" width="50"></center>');

		//Ник
		$name = get_user_color($arr['class'] , $arr['name']);

		//Зарегистрирован
		$date = convent_date($arr['added']);

		//Класс пользователя
		$class = get_user_class_name($arr['class']);


		//Онлайн
		$dt  = get_date_time(gmtime() - 50);
		if($arr['last_access'] > $dt) {
			$online = '<small><font color="#BEBEBE">'.$language['users_15'].'</font></small>';
		}
		//Подключаем шаблон
		require 'templates/'.$config['template'].'/tpl.users.php';
	}
	echo $pagertop;
}

end_frame();
//Подвал
foot();

?>
