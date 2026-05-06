<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

// Блок опросов создан как заготовка и по умолчанию отключен.
// При включении блока отображается только при доступном AJAX-эндпоинте.
?>
<div class="general_box clearfix" data-poll-block="1" style="display:none;">
	<h3>Опрос</h3>
	<div id="poll-block-content">Загрузка опроса...</div>
</div>
<script>
(function(){
	var box = document.querySelector('[data-poll-block="1"]');
	if (!box) {
		return;
	}
	fetch('ajax/poll.php', { credentials: 'same-origin' })
		.then(function (response) { return response.json(); })
		.then(function (payload) {
			if (!payload || !payload.ok || !payload.html) {
				return;
			}
			var body = document.getElementById('poll-block-content');
			if (body) {
				body.innerHTML = payload.html;
			}
			box.style.display = '';
		})
		.catch(function () {});
})();
</script>
