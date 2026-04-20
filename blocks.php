<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Управление блоками
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

if(!$PRIV['EDIT_PRIV']) {
	err('Ошибка' , 'У вас нет прав просматривать данную страницу');
}

$act = (isset($_GET['act']) ? trim((string) $_GET['act']) : '');
$status = (isset($_GET['status']) ? trim((string) $_GET['status']) : '');

function blocks_json_response($ok, $message = '', $extra = array())
{
	header('Content-Type: application/json; charset=UTF-8');

	$payload = array(
		'ok' => ($ok ? 1 : 0),
		'message' => (string) $message,
	);

	if(!empty($extra) && is_array($extra)) {
		foreach($extra AS $key => $value) {
			$payload[$key] = $value;
		}
	}

	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	die();
}

function block_position_name($position)
{
	switch($position) {
		case 'l':
			return 'Слева';

		case 'c':
			return 'По центру сверху';

		case 'd':
			return 'По центру снизу';

		case 'r':
			return 'Справа';

		default:
			return 'Неизвестно';
	}
}

function block_type_name($type)
{
	switch($type) {
		case 'all':
			return 'Всем';

		case 'guests':
			return 'Гостям';

		case 'users':
			return 'Пользователям';

		case 'moderators':
			return 'Модераторам';

		case 'administrators':
			return 'Администраторам';

		default:
			return 'Неизвестно';
	}
}

function block_reindex_position($position)
{
	global $db;

	$allowed = array('l', 'r', 'c', 'd');
	if(!in_array($position, $allowed)) {
		return;
	}

	$sql = $db->query("SELECT bid, weight
					  FROM orbital_blocks
					  WHERE position='".$db->safesql($position)."'
					  ORDER BY weight ASC, bid ASC");

	$weight = 1;
	while($row = $db->get_row($sql)) {
		if((int) $row['weight'] !== $weight) {
			$db->query("UPDATE orbital_blocks SET weight=".$weight." WHERE bid=".(int) $row['bid']);
		}

		$weight++;
	}

	$db->free($sql);
}

//////////////////////////////////////////////
// AJAX-перемещение блока
//////////////////////////////////////////////
if($act == 'move') {
	$bid = (isset($_POST['bid']) ? (int) $_POST['bid'] : (isset($_GET['bid']) ? (int) $_GET['bid'] : 0));
	$direction = trim((string) (isset($_POST['direction']) ? $_POST['direction'] : (isset($_GET['direction']) ? $_GET['direction'] : '')));

	if(!$bid) {
		blocks_json_response(false, 'Не указан блок для перемещения.');
	}

	if($direction != 'up' && $direction != 'down') {
		blocks_json_response(false, 'Неизвестное направление перемещения.');
	}

	$current = $db->super_query("SELECT bid, position, weight
							 FROM orbital_blocks
							 WHERE bid=".$bid." LIMIT 1");

	if(empty($current['bid'])) {
		blocks_json_response(false, 'Блок не найден.');
	}

	$position = $current['position'];
	block_reindex_position($position);

	$current = $db->super_query("SELECT bid, position, weight
							 FROM orbital_blocks
							 WHERE bid=".$bid." LIMIT 1");

	$compare = ($direction == 'up' ? '<' : '>');
	$order = ($direction == 'up' ? 'DESC' : 'ASC');

	$swap = $db->super_query("SELECT bid, weight
						  FROM orbital_blocks
						  WHERE position='".$db->safesql($position)."'
							AND weight ".$compare." ".(int) $current['weight']."
						  ORDER BY weight ".$order.", bid ".$order."
						  LIMIT 1");

	if(empty($swap['bid'])) {
		blocks_json_response(false, 'Блок уже находится на краю списка.');
	}

	$currentBid = (int) $current['bid'];
	$currentWeight = (int) $current['weight'];
	$swapBid = (int) $swap['bid'];
	$swapWeight = (int) $swap['weight'];

	$tempWeightRow = $db->super_query("SELECT MAX(weight) AS c
									 FROM orbital_blocks
									 WHERE position='".$db->safesql($position)."'");
	$tempWeight = ((int) $tempWeightRow['c']) + 1000;

	$db->query("UPDATE orbital_blocks SET weight=".$tempWeight." WHERE bid=".$currentBid);
	$db->query("UPDATE orbital_blocks SET weight=".$currentWeight." WHERE bid=".$swapBid);
	$db->query("UPDATE orbital_blocks SET weight=".$swapWeight." WHERE bid=".$currentBid);

	block_reindex_position($position);
	$memcached->delete('block_'.$position, 0);

	blocks_json_response(true, 'Порядок блоков обновлен.');
}

//////////////////////////////////////////////
//Удаление блока
//////////////////////////////////////////////
if($act == 'del') {
	$bid = (int) $_GET['bid'];
	$sql = $db->query("SELECT * FROM orbital_blocks WHERE bid=".$bid);
	if(!$db->num_rows($sql) ) {
		err('Ошибка' , 'Данный блок не найден' , 1);
	}
	$arr = $db->get_row();

	//Пользователь согласился , удаляем
	if(isset($_GET['take']) && $_GET['take']) {
		$db->query("DELETE FROM orbital_blocks WHERE bid=".$bid);
		block_reindex_position($arr['position']);
		$memcached->delete('block_'.$arr['position'] , 0);
		header('Location:blocks.php?position='.$arr['position'].'&status=2');
		die();
	}

	//Выводим предупреждение
	head('Удаление блока');
	begin_frame('Удаление блока');
	msg('Внимание!' , 'Вы удаляете блок , т.е он не будет больше отображаться на сайте , так и в списке блоков<br><b>Это так ?</b> &nbsp
	<input type="button" value="Да , точно !" onClick="window.location.href=\'blocks.php?act=del&bid='.$bid.'&take=1\'"> &nbsp
	<input type="button" value="Нет , назад" onClick="history.go(-1);"> ');

	end_frame();
	foot();

	die();
}

//////////////////////////////////////////////
//Добавление / редактирование блока
//////////////////////////////////////////////
if($act == 'add') {
	$bid = (isset($_GET['bid']) ? (int) $_GET['bid'] : 0);
	$arr = array(
		'title' => '',
		'blockfile' => '',
		'position' => '',
		'active' => 1,
		'type' => 'all',
		'which' => 'all',
	);

	if($bid) {
		$sql = $db->query("SELECT * FROM orbital_blocks WHERE bid=".$bid);
		if(!$db->num_rows($sql) ) {
			err('Ошибка' , 'Данный блок не найден' , 1);
		}

		$arr = $db->get_row();
	}

	if($_POST) {
		$update = array();
		$positions = array('l', 'r', 'c', 'd');
		$types = array('all', 'guests', 'users', 'moderators', 'administrators');

		$title = trim((string) $_POST['title']);
		if(empty($title) ) {
			err('Ошибка' , 'Вы не ввели название' , 1);
		}

		if(strlen($title) > 60 ) {
			err('Ошибка' , 'Название превышает 60 символов' , 1);
		}
		$update[] = 'title="'.$db->safesql($title).'"';

		$blockfile = trim((string) $_POST['blockfile']);
		if(!is_file('blocks/'.$blockfile) )  {
			err('Ошибка' , 'Выберите файл из списка' , 1);
		}
		$update[] = 'blockfile="'.$db->safesql($blockfile).'"';

		$position = trim((string) $_POST['position']);
		if(!in_array($position , $positions) ) {
			err('Ошибка' , 'Данной позиции не существует' , 1);
		}
		$update[] = 'position="'.$db->safesql($position).'"';

		$active = ((int) $_POST['active'] == 1 ? 1 : 0);
		$update[] = 'active="'.$active.'"';

		$type = trim((string) $_POST['type']);
		if(!in_array($type , $types) ) {
			err('Ошибка' , 'Данного типа не существует' , 1);
		}
		$update[] = 'type="'.$db->safesql($type).'"';

		$which = trim((string) $_POST['which']);
		if($which === '') {
			$which = 'all';
		}
		$update[] = 'which="'.$db->safesql($which).'"';

		if(!$bid) {
			$weight = $db->super_query("SELECT weight AS c FROM orbital_blocks WHERE position='".$db->safesql($position)."' ORDER BY weight DESC LIMIT 1");
			$update[] = 'weight='.(((int) $weight['c']) + 1);
			$db->query("INSERT INTO orbital_blocks SET ".implode(' , ' , $update));
		} else {
			$oldPosition = $arr['position'];
			if($oldPosition != $position) {
				$weight = $db->super_query("SELECT weight AS c FROM orbital_blocks WHERE position='".$db->safesql($position)."' ORDER BY weight DESC LIMIT 1");
				$update[] = 'weight='.(((int) $weight['c']) + 1);
			}

			$db->query("UPDATE orbital_blocks SET ".implode(' , ' , $update)." WHERE bid=".$bid);
		}

		block_reindex_position($position);
		$memcached->delete('block_'.$position , 0);

		if($bid) {
			if($arr['position'] != $position) {
				block_reindex_position($arr['position']);
			}
			$memcached->delete('block_'.$arr['position'] , 0);
		}

		header('Location: blocks.php?status=1&position='.$position);
		die();
	}

	$title = (!$bid ? 'Добавление блока' : 'Редактирование блока');
	head($title);

	begin_frame($title);
	?>
		<form action="blocks.php?act=add&bid=<?=$bid;?>" method="post">
		<table width="40%" align="center">
			<tr>
				<td width="10%"><b>Название:</b></td>
				<td><input type="text" name="title" value="<?=htmlspecialchars($arr['title']);?>" size="50"></td>
			</tr>

			<tr>
				<td width="10%"><b>Файл:</b></td>
				<td>
					<select name="blockfile">
					<option value="0">(Выберите)</option>
					<?
						$open = opendir('blocks');
						while($file = readdir($open)) {
							if($file != '.' && $file != '..' && $file != '.htaccess' && !is_dir('blocks/'.$file)) {
								echo '<option value="'.$file.'" '.($arr['blockfile'] == $file ? 'selected' : '').'>'.$file.'</option>';
							}
						}
						closedir($open);
					?>
					</select>
				</td>
			</tr>

			<tr>
				<td width="10%"><b>Позиция:</b></td>
				<td>
					<select name="position">
					<option value="">(Выберите)</option>
					<option value="l" <?=($arr['position'] == 'l' ? 'selected' : '' );?>>Левый</option>
					<option value="c" <?=($arr['position'] == 'c' ? 'selected' : '' );?>>Центральный (вверху)</option>
					<option value="d" <?=($arr['position'] == 'd' ? 'selected' : '' );?>>Центральный (внизу)</option>
					<option value="r" <?=($arr['position'] == 'r' ? 'selected' : '' );?>>Справа</option>
					</select>
				</td>
			</tr>

			<tr>
				<td width="10%"><b>Активен:</b></td>
				<td>
					<select name="active">
					<option value="1" <?=($arr['active'] == '1' ? 'selected' : '' );?>>Да</option>
					<option value="0" <?=($arr['active'] == '0' ? 'selected' : '' );?>>Нет</option>
					</select>
				</td>
			</tr>

			<tr>
				<td width="10%"><b>Тип:</b></td>
				<td>
					<select name="type">
					<option value="all" <?=($arr['type'] == 'all' ? 'selected' : '' );?>>Всем</option>
					<option value="guests" <?=($arr['type'] == 'guests' ? 'selected' : '' );?>>Гостям</option>
					<option value="users" <?=($arr['type'] == 'users' ? 'selected' : '' );?>>Пользователям</option>
					<option value="moderators" <?=($arr['type'] == 'moderators' ? 'selected' : '' );?>>Модераторам</option>
					<option value="administrators" <?=($arr['type'] == 'administrators' ? 'selected' : '' );?>>Администраторам</option>
					</select>
				</td>
			</tr>

			<tr>
				<td width="10%"><b>Зона видимости:</b></td>
				<td>
					<input type="text" name="which" value="<?=(!$bid ? 'all' : htmlspecialchars($arr['which']) );?>"><br>
					<small>Вводите имя файла (без .php), если хотите, чтобы блок отображался там (через запятую, например: index,login,signup). <b>all</b> - везде.</small>
				</td>
			</tr>

			<tr>
				<td width="10%"></td>
				<td>
					<input type="submit" value="Выполнить">
				</td>
			</tr>
		</table>
		</form>
	<?
	end_frame();
	foot();
	die();
}

//////////////////////////////////////////////
//Вывод блоков
//////////////////////////////////////////////
$position = trim((string) (isset($_GET['position']) ? $_GET['position'] : ''));
$positions = array('l', 'c', 'd', 'r');
if(!in_array($position, $positions)) {
	$position = '';
}

$sql = false;
$blocks = array();

if($position != '') {
	block_reindex_position($position);
	$sql = $db->query("SELECT *
					  FROM orbital_blocks
					  WHERE position='".$db->safesql($position)."'
					  ORDER BY weight ASC, bid ASC");

	while($row = $db->get_row($sql)) {
		$blocks[] = $row;
	}
}

head('Управление блоками');

switch($status) {
	case '1':
		msg('Успешно', 'Задание выполнено');
	break;

	case '2':
		msg('Успешно', 'Блок удален');
	break;
}

begin_frame('Управление блоками');

if(!$config['blocks_use']) {
	msg('Внимание!' , 'Блочная система отключена локально! Вы можете редактировать блоки, но никто их не увидит! Включить блоки можно в system/config.php.');
}
?>

<div style="margin-bottom:12px;">
	<b>Позиция:</b>
	<a href="blocks.php?position=l" style="<?=($position == 'l' ? 'font-weight:bold;text-decoration:underline;' : '');?>">Слева</a> |
	<a href="blocks.php?position=c" style="<?=($position == 'c' ? 'font-weight:bold;text-decoration:underline;' : '');?>">По центру сверху</a> |
	<a href="blocks.php?position=d" style="<?=($position == 'd' ? 'font-weight:bold;text-decoration:underline;' : '');?>">По центру снизу</a> |
	<a href="blocks.php?position=r" style="<?=($position == 'r' ? 'font-weight:bold;text-decoration:underline;' : '');?>">Справа</a>
	<span style="float:right;"><a href="blocks.php?act=add"><b>+ Добавить блок</b></a></span>
</div>
<div style="clear:both;"></div>

<?
if($position == '') {
	msg('Внимание', 'Выберите позицию блока.');
} elseif(empty($blocks)) {
	msg('Внимание', 'Ни одного блока не найдено.');
} else {
	?>
	<div id="block-move-status" style="display:block;min-height:16px;margin-bottom:8px;color:#008000;"></div>
	<table width="100%" cellpadding="4" cellspacing="0">
		<tr>
			<td width="5%"><u>#</u></td>
			<td width="27%"><u>Блок</u></td>
			<td width="10%"><u>Активен</u></td>
			<td width="14%"><u>Видимость</u></td>
			<td width="20%"><u>Где виден</u></td>
			<td width="12%"><u>Порядок</u></td>
			<td width="12%"><u>Действия</u></td>
		</tr>

		<?
		$total = count($blocks);
		for($i = 0; $i < $total; $i++) {
			$arr = $blocks[$i];
			$isFirst = ($i == 0);
			$isLast = ($i == ($total - 1));
			?>
			<tr class="js-block-row" data-bid="<?=$arr['bid'];?>">
				<td>#<?=$arr['bid'];?></td>
				<td>
					<b><?=htmlspecialchars($arr['title']);?></b><br>
					<small><?=htmlspecialchars($arr['blockfile']);?>, <?=block_position_name($arr['position']);?></small>
				</td>
				<td><?=($arr['active'] ? 'Да' : 'Нет');?></td>
				<td><?=block_type_name($arr['type']);?></td>
				<td>
					<?
					if($arr['which'] == 'all') {
						echo 'Везде';
					} elseif(!empty($arr['which'])) {
						$which = explode(',', $arr['which']);
						$resource = array();

						foreach($which AS $row) {
							$row = preg_replace('~[^a-z0-9_\-]~i', '', trim($row));
							if($row == '') {
								continue;
							}

							$resource[] = '<a href="'.$row.'.php" target="_blank">'.$row.'.php</a>';
						}

						echo (!empty($resource) ? implode(', ', $resource) : 'Неизвестно');
					} else {
						echo 'Неизвестно';
					}
					?>
				</td>
				<td>
					<a href="#" class="js-block-move js-move-up" data-direction="up" style="<?=($isFirst ? 'display:none;' : '');?>">Вверх</a>
					|
					<a href="#" class="js-block-move js-move-down" data-direction="down" style="<?=($isLast ? 'display:none;' : '');?>">Вниз</a>
				</td>
				<td>
					<a href="blocks.php?act=add&bid=<?=$arr['bid'];?>">Редактировать</a>
					|
					<a href="blocks.php?act=del&bid=<?=$arr['bid'];?>">Удалить</a>
				</td>
			</tr>
			<?
		}
		?>
	</table>

	<script type="text/javascript">
	(function($){
		if(!$) {
			return;
		}

		function refreshMoveControls() {
			var rows = $('.js-block-row');
			rows.find('.js-move-up, .js-move-down').show();
			rows.first().find('.js-move-up').hide();
			rows.last().find('.js-move-down').hide();
		}

		function setMoveStatus(message, isError) {
			$('#block-move-status')
				.css('color', (isError ? '#AA0000' : '#008000'))
				.html(message);
		}

		$('.js-block-move').click(function(){
			var link = $(this);
			if(link.data('busy')) {
				return false;
			}

			var row = link.closest('.js-block-row');
			if(!row.length) {
				return false;
			}

			var direction = link.attr('data-direction');
			link.data('busy', 1);
			setMoveStatus('Сохраняем порядок...', false);

			$.ajax({
				type: 'POST',
				url: 'blocks.php?act=move',
				dataType: 'json',
				data: {
					bid: row.attr('data-bid'),
					direction: direction
				},
				success: function(response){
					if(!response || parseInt(response.ok, 10) !== 1) {
						setMoveStatus((response && response.message ? response.message : 'Ошибка перемещения блока.'), true);
						return;
					}

					if(direction == 'up') {
						var prev = row.prev('.js-block-row');
						if(prev.length) {
							prev.before(row);
						}
					} else {
						var next = row.next('.js-block-row');
						if(next.length) {
							next.after(row);
						}
					}

					refreshMoveControls();
					setMoveStatus((response.message ? response.message : 'Порядок обновлен.'), false);
				},
				error: function(){
					setMoveStatus('Сервер временно недоступен. Попробуйте еще раз.', true);
				},
				complete: function(){
					link.data('busy', 0);
				}
			});

			return false;
		});

		refreshMoveControls();
	})(window.jQuery);
	</script>
	<?
}

end_frame();
foot();
?>
