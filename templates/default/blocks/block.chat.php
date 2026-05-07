<?php
if (!defined('LITETRACKER')) {
die('Direct access denied.');
}

global $config, $PRIV, $USER, $language;
?>

<section class="frame chat-frame">
<header class="frame-header">
<h2 class="frame-title">Чат</h2>
<?php if ($PRIV['chat_clear']) { ?>
<a class="chat-clear-link" href="javascript:void(0);" onclick="chatClear();" title="<?=htmlspecialchars($language['chat_2'], ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars($language['chat_2'], ENT_QUOTES, 'UTF-8');?></a>
<?php } ?>
</header>
<div class="frame-body chat-body">
<?php if ($USER) { ?>
<div class="chat-input-row">
<input type="text" id="text_chat" class="chat-input" placeholder="<?=htmlspecialchars($language['chat_3'], ENT_QUOTES, 'UTF-8');?>" maxlength="250" autocomplete="off">
<button id="chat_send_btn" class="chat-send-btn" type="button" onclick="chatSend();"><?=htmlspecialchars($language['chat_3'], ENT_QUOTES, 'UTF-8');?></button>
</div>
<div id="result_send" class="chat-send-status"></div>
<?php } ?>
<div id="result_chat" class="chat-messages-area">
<div class="chat-notice">Загрузка…</div>
</div>
</div>
</section>
