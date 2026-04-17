///////////////////////////////////////////////////////////////////////
// Комментирование
///////////////////////////////////////////////////////////////////////

function getCommentTextarea() {
	var field = document.getElementById('wall-comment-text');
	if (field) {
		return field;
	}

	field = document.querySelector("textarea[name='text']");
	if (field) {
		return field;
	}

	field = document.querySelector("textarea[name='textComment']");
	if (field) {
		return field;
	}

	return null;
}

function safeSlideDown(selector, callback) {
	if (typeof window.jQuery === 'undefined') {
		var el = document.querySelector(selector);
		if (el) {
			el.style.display = '';
		}
		if (typeof callback === 'function') {
			callback();
		}
		return;
	}

	window.jQuery(selector).stop(true, true).slideDown(350, function () {
		if (typeof callback === 'function') {
			callback();
		}
	});
}

function safeSlideUp(selector, callback) {
	if (typeof window.jQuery === 'undefined') {
		var el = document.querySelector(selector);
		if (el) {
			el.style.display = 'none';
		}
		if (typeof callback === 'function') {
			callback();
		}
		return;
	}

	window.jQuery(selector).stop(true, true).slideUp(350, function () {
		if (typeof callback === 'function') {
			callback();
		}
	});
}

function safeSliderElement(selector, speed) {
	if (typeof window.sliderElement === 'function') {
		window.sliderElement(selector, speed);
	}
}

// Возврат к форме
function upCommentForm() {
	var field = getCommentTextarea();
	if (field) {
		field.value = '';
	}

	safeSlideDown('#addComment', function () {
		safeSliderElement('#addComment', 800);
	});

	return false;
}

// Скрытие формы
function downCommentForm() {
	var addComment = document.getElementById('addComment');
	if (!addComment) {
		return false;
	}

	var isVisible = true;
	if (typeof window.jQuery !== 'undefined') {
		isVisible = window.jQuery('#addComment').is(':visible');
	} else {
		isVisible = addComment.style.display !== 'none';
	}

	if (isVisible) {
		safeSlideUp('#addComment', function () {
			safeSliderElement('#setComment', 800);
		});
	}

	return false;
}

function replyWallComment(userName) {
	var field = getCommentTextarea();
	if (!field) {
		return false;
	}

	var cleanUserName = String(userName || '').replace(/\s+/g, ' ').trim();
	if (!cleanUserName) {
		field.focus();
		return false;
	}

	var prefix = '[b]' + cleanUserName + '[/b], ';
	if (field.value.indexOf(prefix) !== 0) {
		field.value = prefix + field.value;
	}

	field.focus();

	if (typeof field.setSelectionRange === 'function') {
		var pos = field.value.length;
		field.setSelectionRange(pos, pos);
	}

	return false;
}
