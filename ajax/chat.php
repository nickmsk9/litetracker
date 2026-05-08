<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Ajax чат (Memcached, без MySQL)
===================================================================
*/

// Allow chat actions only via same-origin POST AJAX/fetch requests
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$isXmlHttpRequest = (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest');
$secFetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
$isSameSiteFetch = ($secFetchSite === 'same-origin');

if ($requestMethod !== 'POST') {
	http_response_code(405);
	die();
}
if (!$isXmlHttpRequest && !$isSameSiteFetch) {
	http_response_code(403);
	die();
}

require '../system/init.php';

global $language;
header('Content-Type: text/html; charset=' . $language['charset']);

if (!$PRIV['chat_view']) {
	echo chat_msg($language['chat_10']);
	die();
}

define('CHAT_MAX_MESSAGES', 50);
define('CHAT_MC_KEY', 'chat_msgs_v2');

function chat_msg($text)
{
	return '<div class="chat-notice">' . htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8') . '</div>';
}

function chat_load_messages()
{
	global $memcached;
	$raw = $memcached->get(CHAT_MC_KEY);
	if (!is_array($raw)) {
		return array();
	}
	return $raw;
}

function chat_save_messages(array $messages)
{
	global $memcached;
	$memcached->set(CHAT_MC_KEY, $messages, 0, 0);
}

function chat_generate_id()
{
	return sprintf('%010d_%04d', time(), mt_rand(0, 9999));
}

function chat_render_message(array $msg, $currentUserId, $canDelete)
{
	$id = htmlspecialchars((string) $msg['id'], ENT_QUOTES, 'UTF-8');
	$text = format_comment((string) ($msg['text'] ?? ''));
	$username = htmlspecialchars((string) ($msg['username'] ?? ''), ENT_QUOTES, 'UTF-8');
	$dateParts = explode(' ', (string) ($msg['date'] ?? ''));
	$timeParts = isset($dateParts[1]) ? explode(':', $dateParts[1]) : array('00', '00');
	$time = ($timeParts[0] ?? '00') . ':' . str_pad((string) ($timeParts[1] ?? '0'), 2, '0', STR_PAD_LEFT);
	$msgUserId = (int) ($msg['id_user'] ?? 0);
	$userclass = (int) ($msg['userclass'] ?? 0);
	$isPrivate = !empty($msg['private_to']);

	$html = '<div id="msg_' . $id . '" class="chat-message' . ($isPrivate ? ' chat-message-private' : '') . '">';
	$html .= '<table width="100%" cellpadding="0"><tr>';
	$html .= '<td width="7%">';
	if ($canDelete) {
		$html .= '<small><a href="javascript:void(0);" data-chat-delete="' . $id . '"><img src="public/images/broom.png" border="0" title="' . htmlspecialchars($language['chat_8'], ENT_QUOTES, 'UTF-8') . '"/></a></small>&nbsp;';
	}
	$html .= '<small><a href="' . profile_href($msgUserId) . '"><img src="public/images/users.png" border="0" title="' . htmlspecialchars($language['chat_9'], ENT_QUOTES, 'UTF-8') . '"/></a></small>&nbsp;';
	$html .= '</td>';
	$html .= '<td width="12%">';
	$html .= '<a href="javascript:void(0);" data-chat-mention="' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '">' . get_user_color($userclass, $username) . '</a>:';
	if ($isPrivate) {
		$html .= ' <em class="chat-private-label">приват</em>';
	}
	$html .= '</td>';
	$html .= '<td width="68%">' . $text . '</td>';
	$html .= '<td width="13%"><div style="float:right"><small>' . $time . '</small></div></td>';
	$html .= '</tr></table></div>';
	return $html;
}

$type = trim((string) ($_POST['type'] ?? ''));
$currentUserId = (int) ($USER['id'] ?? 0);
$canDelete = !empty($PRIV['chat_delete']);
$canClear = !empty($PRIV['chat_clear']);

// ======================================================
// Update (load messages)
// ======================================================
if ($type === 'update') {
	$messages = chat_load_messages();

	if (empty($messages)) {
		echo chat_msg($language['chat_4']);
		die();
	}

	// Show messages in reverse (newest last)
	$visible = array();
	foreach ($messages as $msg) {
		$privTo = (int) ($msg['private_to'] ?? 0);
		$fromId = (int) ($msg['id_user'] ?? 0);
		// Show if: public, or current user is sender, or current user is recipient
		if ($privTo === 0 || $fromId === $currentUserId || $privTo === $currentUserId) {
			$visible[] = $msg;
		}
	}

	if (empty($visible)) {
		echo chat_msg($language['chat_4']);
		die();
	}

	$html = '';
	foreach (array_reverse($visible) as $msg) {
		$html .= chat_render_message($msg, $currentUserId, $canDelete);
	}
	echo $html;
	die();
}

// ======================================================
// Send message
// ======================================================
if ($type === 'send') {
	if (!$USER) {
		die();
	}

	// Rate limit via Memcached
	$rateKey = 'chat_rate_' . $currentUserId;
	if ($memcached->get($rateKey) !== false) {
		echo chat_msg(sprintf($language['chat_5'], (int) ($config['chat_limit'] ?? 5)));
		die();
	}

	$text = trim((string) ($_POST['text'] ?? ''));
	if (empty($text)) {
		echo chat_msg($language['chat_6']);
		die();
	}

	$maxLen = (int) ($config['chat_limit_text'] ?? 250);
	if (mb_strlen($text, 'UTF-8') > $maxLen) {
		echo chat_msg(sprintf($language['chat_6'], $maxLen));
		die();
	}

	$privateToId = 0;
	$privateToName = '';

	// Parse /pm command: /pm username message
	if (strncmp($text, '/pm ', 4) === 0) {
		$pmBody = substr($text, 4);
		$spacePos = strpos($pmBody, ' ');
		if ($spacePos !== false) {
			$privateToName = trim(substr($pmBody, 0, $spacePos));
			$pmText = trim(substr($pmBody, $spacePos + 1));
			if ($privateToName !== '' && $pmText !== '') {
				$pmUser = $db->super_query("SELECT id FROM users WHERE LOWER(name) = LOWER('" . $db->safesql($privateToName) . "') LIMIT 1");
				if (!empty($pmUser['id'])) {
					$privateToId = (int) $pmUser['id'];
					$text = $pmText;
				}
			}
		}
	}

	$msg = array(
		'id'         => chat_generate_id(),
		'id_user'    => $currentUserId,
		'username'   => (string) $USER['name'],
		'userclass'  => (int) ($USER['class'] ?? 0),
		'date'       => date('Y-m-d H:i:s'),
		'text'       => $text,
		'private_to' => $privateToId,
	);

	$messages = chat_load_messages();
	array_unshift($messages, $msg);
	if (count($messages) > CHAT_MAX_MESSAGES) {
		$messages = array_slice($messages, 0, CHAT_MAX_MESSAGES);
	}
	chat_save_messages($messages);

	// Set rate limit with TTL
	$rateTtl = max(1, (int) ($config['chat_limit'] ?? 5));
	$memcached->set($rateKey, 1, 0, $rateTtl);

	// Update last_chat timestamp in users table (non-critical, column may not exist)
	if (isset($db)) {
		$db->query("UPDATE users SET last_chat = " . time() . " WHERE id = " . $currentUserId, 0);
	}

	echo '';
	die();
}

// ======================================================
// Delete message
// ======================================================
if ($type === 'delete') {
	if (!$canDelete) {
		die();
	}

	$msgId = trim((string) ($_POST['id'] ?? ''));
	if ($msgId === '') {
		die();
	}

	$messages = chat_load_messages();
	$messages = array_values(array_filter($messages, function ($m) use ($msgId) {
		return (string) ($m['id'] ?? '') !== $msgId;
	}));
	chat_save_messages($messages);
	die();
}

// ======================================================
// Clear chat
// ======================================================
if ($type === 'clear') {
	if (!$canClear) {
		die();
	}

	$memcached->delete(CHAT_MC_KEY, 0);
	echo chat_msg($language['chat_4']);
	die();
}
