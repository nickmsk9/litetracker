<?php

require '../system/init.php';

header('Content-Type: application/json; charset=UTF-8');

$objectType = preg_replace('~[^a-z0-9_]~i', '', (string) ($_REQUEST['object_type'] ?? ''));
$objectId = (int) ($_REQUEST['object_id'] ?? 0);

if ($objectType === '' || $objectId <= 0) {
	echo json_encode(array('ok' => 0, 'message' => 'Некорректный запрос.'), JSON_UNESCAPED_UNICODE);
	die();
}

$reactionUsers = lt_reaction_users($objectType, $objectId);
$html = '';

if ($reactionUsers) {
	$html .= '<div class="plus-reaction-users">';
	foreach ($reactionUsers as $reactionUser) {
		$reactionLabel = ($reactionUser['reaction'] === 'dislike' ? 'дизлайк' : 'лайк');
		$html .= '<div class="plus-reaction-user-row">';
		$html .= '<a href="'.profile_href($reactionUser).'">'.get_user_color((int) $reactionUser['class'], htmlspecialchars((string) $reactionUser['name'], ENT_QUOTES, 'UTF-8'), $reactionUser).'</a>';
		$html .= '<span>'.htmlspecialchars($reactionLabel, ENT_QUOTES, 'UTF-8').'</span>';
		$html .= '</div>';
	}
	$html .= '</div>';
} else {
	$html .= '<div class="profile-empty-state">Оценок пока нет.</div>';
}

echo json_encode(
	array(
		'ok' => 1,
		'html' => $html,
	),
	JSON_UNESCAPED_UNICODE
);
die();
?>
