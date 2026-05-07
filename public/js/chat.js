///////////////////////////////////////////////////////////////////////
// LiteTracker Chat (Memcached-powered, no MySQL)
///////////////////////////////////////////////////////////////////////

var chatUpdateTimer = null;
var CHAT_AJAX_URL = 'ajax/chat.php';

function chatUpdate() {
$.post(CHAT_AJAX_URL, { type: 'update' }, function (response) {
$('#result_chat').html(response);
}, 'html').always(function () {
chatUpdateTimer = setTimeout(chatUpdate, 3000);
});
}

function chatSend() {
var $btn = $('#chat_send_btn');
var $input = $('#text_chat');
var text = $input.val();

if (!text) {
return false;
}

$btn.prop('disabled', true);

$.post(CHAT_AJAX_URL, { type: 'send', text: text }, function (response) {
$('#result_send').html(response);
if (!response) {
$input.val('');
clearTimeout(chatUpdateTimer);
chatUpdate();
}
}, 'html').always(function () {
$btn.prop('disabled', false);
});

return false;
}

function chatDelete(id) {
if (!confirm('Удалить сообщение?')) {
return false;
}
$.post(CHAT_AJAX_URL, { type: 'delete', id: id }, function () {
$('#msg_' + id).fadeOut(200, function () { $(this).remove(); });
}, 'html');
return false;
}

function chatClear() {
if (!confirm('Очистить чат?')) {
return false;
}
$.post(CHAT_AJAX_URL, { type: 'clear' }, function (response) {
$('#result_chat').html(response);
}, 'html');
return false;
}

function chatMention(username) {
var $input = $('#text_chat');
var val = $input.val();
$input.val('[b]' + username + '[/b]: ' + val);
$input.focus();
}

// Send on Enter key
$(document).on('keydown', '#text_chat', function (e) {
if (e.key === 'Enter' && !e.shiftKey) {
e.preventDefault();
chatSend();
}
});

// Start polling
$(function () {
chatUpdate();
});
