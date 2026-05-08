///////////////////////////////////////////////////////////////////////
// LiteTracker Chat (Memcached-powered, no MySQL)
///////////////////////////////////////////////////////////////////////

var chatUpdateTimer = null;
var CHAT_AJAX_URL = (
typeof window.LT_CHAT_AJAX_URL === 'string' && window.LT_CHAT_AJAX_URL !== ''
? window.LT_CHAT_AJAX_URL
: '/ajax/chat.php'
);
var CHAT_POLL_INTERVAL_MS = 3000;
var CHAT_REQUEST_TIMEOUT_MS = 10000;
var CHAT_ERROR_MESSAGE = '<div class="chat-notice">Чат временно недоступен. Попробуйте обновить страницу.</div>';

// Escape special characters for jQuery selectors (fallback for jQuery < 3.0)
function chatEscapeSelector(value) {
return value.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, '\\$&');
}

function chatUpdate() {
$.ajax({
url: CHAT_AJAX_URL,
method: 'POST',
data: { type: 'update' },
dataType: 'html',
timeout: CHAT_REQUEST_TIMEOUT_MS
}).done(function (response) {
$('#result_chat').html(response);
}).fail(function () {
$('#result_chat').html(CHAT_ERROR_MESSAGE);
}).always(function () {
chatUpdateTimer = setTimeout(chatUpdate, CHAT_POLL_INTERVAL_MS);
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

$.ajax({
url: CHAT_AJAX_URL,
method: 'POST',
data: { type: 'send', text: text },
dataType: 'html',
timeout: CHAT_REQUEST_TIMEOUT_MS
}).done(function (response) {
$('#result_send').html(response);
if (!response) {
$input.val('');
clearTimeout(chatUpdateTimer);
chatUpdate();
}
}).fail(function () {
$('#result_send').html(CHAT_ERROR_MESSAGE);
}).always(function () {
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
}, 'html').fail(function () {
$('#result_chat').html(CHAT_ERROR_MESSAGE);
});
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
