///////////////////////////////////////////////////////////////////////
// LiteTracker Chat (Memcached-powered, no MySQL)
///////////////////////////////////////////////////////////////////////

var chatUpdateTimer = null;
var CHAT_AJAX_URL = 'ajax/chat.php';

// Escape special characters for jQuery selectors (fallback for jQuery < 3.0)
function chatEscapeSelector(value) {
return value.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, '\\$&');
}

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

function chatClear() {
if (!confirm('Очистить чат?')) {
return false;
}
$.post(CHAT_AJAX_URL, { type: 'clear' }, function (response) {
$('#result_chat').html(response);
}, 'html');
return false;
}

// Event delegation for delete and mention actions
$(document).on('click', '[data-chat-delete]', function (e) {
e.preventDefault();
var id = $(this).attr('data-chat-delete') || '';
if (!id || !confirm('Удалить сообщение?')) {
return;
}
$.post(CHAT_AJAX_URL, { type: 'delete', id: id }, function () {
$('#msg_' + chatEscapeSelector(id)).fadeOut(200, function () { $(this).remove(); });
}, 'html');
});

$(document).on('click', '[data-chat-mention]', function (e) {
e.preventDefault();
var username = $(this).attr('data-chat-mention') || '';
if (!username) {
return;
}
var $input = $('#text_chat');
$input.val('[b]' + username + '[/b]: ' + $input.val());
$input.focus();
});

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
