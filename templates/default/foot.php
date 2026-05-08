<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

$showStandardSidebar = (!defined('LIGHT') && !empty($GLOBALS['LITETRACKER_STANDARD_SIDEBAR']) && empty($GLOBALS['LITETRACKER_HIDE_STANDARD_SIDEBAR']));
$showBottomBlocks = empty($GLOBALS['LITETRACKER_HIDE_BOTTOM_BLOCKS']);
?>
			</div>

			<?php if ($showBottomBlocks) { ?>
			<div class="site-bottom-blocks">
				<?php show_blocks('d'); ?>
			</div>
			<?php } ?>
		</main>

		<?php if ($showStandardSidebar) { ?>
			<?php render_standard_sidebar(); ?>
		<?php } ?>
	</div>
</div>
</div>

<div style="clear: both;"></div>
<footer class="footer">
	<div class="wrapper pd20 clearfix">
		<div class="pull-left mr20">
			<a href="/" class="logo">LiteTracker</a>
		</div>

		<div class="pull-left">
			<div>LiteTracker &copy; 2026</div>

			<div class="clearfix">
				<div class="pull-left mr40"><a href="/disclaimer.php" class="u">Пользовательское соглашение</a></div>
				<div class="pull-left mr40"><a href="/complaint.php" class="u">Правообладателям</a></div>
				<div class="pull-left mr40"><a href="/avatars.php" class="u">Аватары</a></div>
				<div class="pull-left mr40"><a href="/faq.php" class="u">FAQ</a></div>
				<div class="pull-left mr40"><a href="/rules.php" class="u">Правила</a></div>
				<div class="pull-left"><a href="/feedback.php" class="u" data-feedback-open>Обратная связь</a></div>
			</div>
		</div>
	</div>

	<hr class="m0">

	<div class="wrapper pd20 clearfix">
		<div class="adults-only pull-left mr20">
			<i class="s-icons-18plus iblock pull-left mr20"></i>
			<div class="oh">Сайт может содержать материалы не&nbsp;предназначенные для лиц младше 18&nbsp;лет.</div>
		</div>

		<div class="social-links pull-right clearfix">
			<a target="_blank" href="https://www.facebook.com/animelayer" class="iblock pull-left mr10 s-icons-facebook"></a>
			<a target="_blank" href="https://twitter.com/animelayer" class="iblock pull-left mr10 s-icons-twitter"></a>
			<a target="_blank" href="/rss/" class="iblock pull-left s-icons-rss"></a>
		</div>
	</div>
</footer>
</div>

<div class="feedback-modal" data-feedback-modal hidden>
	<div class="feedback-modal-backdrop" data-feedback-close></div>
	<div class="feedback-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="feedback-modal-title">
		<form class="feedback-form" action="/feedback.php" method="post" data-feedback-form>
			<?=lt_csrf_input('feedback_form');?>
			<div class="feedback-form-body">
				<h2 class="feedback-title" id="feedback-modal-title">Обратная связь</h2>

				<label class="feedback-field">
					<span class="feedback-label">Тема</span>
					<select name="topic" required>
						<option value="" selected disabled>Выберите тему</option>
						<option value="auth">Проблемы с авторизацией и регистрацией</option>
						<option value="ideas">Предложения и пожелания</option>
						<option value="bugs">Ошибки на сайте</option>
						<option value="ads">Реклама на сайте</option>
						<option value="other">Прочее</option>
					</select>
				</label>

				<label class="feedback-field">
					<span class="feedback-label">Сообщение</span>
					<textarea name="message" required placeholder="Опишите вопрос или проблему"></textarea>
				</label>

				<div class="feedback-message" data-feedback-message hidden></div>
			</div>

			<div class="feedback-form-footer">
				<button class="feedback-submit" type="submit">Отправить</button>
			</div>
		</form>
	</div>
</div>

<script type="text/javascript" src="/public/js/lt.ajax.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var modal = document.querySelector('[data-feedback-modal]');
	var openers = document.querySelectorAll('[data-feedback-open]');
	var closers = document.querySelectorAll('[data-feedback-close]');
	var form = document.querySelector('[data-feedback-form]');
	var message = document.querySelector('[data-feedback-message]');

	if (!modal || !form) {
		return;
	}

	function setMessage(text, isError) {
		if (!message) {
			return;
		}
		message.hidden = !text;
		message.textContent = text || '';
		message.classList.toggle('feedback-message-error', !!isError);
	}

	function openModal() {
		modal.hidden = false;
		document.body.classList.add('feedback-modal-open');
		setMessage('', false);
		var topic = form.querySelector('select[name="topic"]');
		if (topic) {
			topic.focus();
		}
	}

	function closeModal() {
		modal.hidden = true;
		document.body.classList.remove('feedback-modal-open');
	}

	for (var i = 0; i < openers.length; i++) {
		openers[i].addEventListener('click', function (event) {
			event.preventDefault();
			openModal();
		});
	}

	for (var j = 0; j < closers.length; j++) {
		closers[j].addEventListener('click', closeModal);
	}

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && !modal.hidden) {
			closeModal();
		}
	});

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		var submit = form.querySelector('button[type="submit"]');
		var formData = new FormData(form);
		if (submit) {
			submit.disabled = true;
			submit.textContent = 'Отправка...';
		}
		setMessage('', false);

		fetch(form.getAttribute('action'), {
			method: 'POST',
			body: formData,
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'application/json'
			}
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				setMessage(payload.message || '', !payload.ok);
				if (payload.ok) {
					form.reset();
				}
			})
			.catch(function () {
				setMessage('Не удалось отправить сообщение. Попробуйте ещё раз.', true);
			})
			.then(function () {
				if (submit) {
					submit.disabled = false;
					submit.textContent = 'Отправить';
				}
			});
	});
});
</script>

</body>
</html>
