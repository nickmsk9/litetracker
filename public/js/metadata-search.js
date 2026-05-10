(function () {
	'use strict';

	function ready(callback) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback);
			return;
		}

		callback();
	}

	function text(value) {
		return String(value || '').trim();
	}

	function yearFromText(value) {
		var match = String(value || '').match(/\b(19|20)\d{2}\b/);
		return match ? match[0] : '';
	}

	function inputByName(form, name) {
		return form.querySelector('[name="' + name + '"]');
	}

	function checkedType(form) {
		var input = form.querySelector('input[name="content_type"]:checked');
		return input ? text(input.value) : '';
	}

	function optionText(select) {
		if (!select || !select.options || select.selectedIndex < 0) {
			return '';
		}

		var option = select.options[select.selectedIndex];
		return text(option.getAttribute('data-template-key') || option.textContent || '');
	}

	function templateField(form, labels) {
		var fields = form.querySelectorAll('[data-template-label]');
		var wanted = labels.map(function (label) {
			return text(label).toLowerCase();
		});

		for (var i = 0; i < fields.length; i++) {
			var label = text(fields[i].getAttribute('data-template-label')).toLowerCase();
			if (wanted.indexOf(label) === -1) {
				continue;
			}

			var input = fields[i].querySelector('input, textarea');
			if (input) {
				return input;
			}
		}

		return null;
	}

	function currentYear(form) {
		var yearInput = templateField(form, ['Год выхода', 'Год выпуска', 'Дата выпуска']);
		var titleInput = inputByName(form, 'name');
		return text(yearInput ? yearInput.value : '') || yearFromText(titleInput ? titleInput.value : '');
	}

	function setStatus(root, message, type) {
		var status = root.querySelector('[data-metadata-search-status]');
		if (!status) {
			return;
		}

		status.textContent = message || '';
		status.classList.toggle('is-error', type === 'error');
	}

	function clearResults(root) {
		var results = root.querySelector('[data-metadata-search-results]');
		if (results) {
			results.innerHTML = '';
		}
	}

	function renderResults(root, form, items) {
		var results = root.querySelector('[data-metadata-search-results]');
		if (!results) {
			return;
		}

		results.innerHTML = '';
		items.forEach(function (item) {
			var card = document.createElement('div');
			var body = document.createElement('div');
			var title = document.createElement('div');
			var meta = document.createElement('div');
			var preview = document.createElement('div');
			var actions = document.createElement('div');
			var source = document.createElement('a');
			var warning = text(item.warning);
			var description = text(item.description);
			var descriptionLang = text(item.description_lang || '');

			card.className = 'metadata-result metadata-result-no-poster';
			body.className = 'metadata-result-body';
			title.className = 'metadata-result-title';
			title.textContent = text(item.title) || 'Без названия';
			meta.className = 'metadata-result-meta';
			meta.textContent = [
				text(item.provider),
				text(item.year),
				item.score ? 'score ' + text(item.score) : ''
			].filter(Boolean).join(' · ');

			body.appendChild(title);
			body.appendChild(meta);

			if (item.source_url) {
				source.className = 'metadata-result-source';
				source.href = item.source_url;
				source.target = '_blank';
				source.rel = 'noopener noreferrer';
				source.textContent = item.source_url;
				body.appendChild(source);
			}

			if (description) {
				preview.className = 'metadata-result-description';
				preview.textContent = description;
				body.appendChild(preview);
			}

			if (warning) {
				var warningBox = document.createElement('div');
				warningBox.className = 'metadata-result-warning';
				warningBox.textContent = warning;
				body.appendChild(warningBox);
			}

			actions.className = 'metadata-result-actions';
			if (description) {
				var button = document.createElement('button');
				button.type = 'button';
				button.className = 'metadata-result-insert';
				button.textContent = descriptionLang === 'ru' ? 'Вставить' : 'Вставить не на русском';
				button.addEventListener('click', function () {
					insertDescription(root, form, item);
				});
				actions.appendChild(button);
			}
			body.appendChild(actions);
			card.appendChild(body);
			results.appendChild(card);
		});
	}

	function insertDescription(root, form, item) {
		var description = text(item.description);
		var descriptionInput = templateField(form, ['Описание']);
		var descriptionLang = text(item.description_lang || '');

		if (!description || !descriptionInput) {
			return;
		}

		if (descriptionLang !== 'ru' && !window.confirm('Описание не на русском языке. Всё равно вставить?')) {
			return;
		}

		if (text(descriptionInput.value) !== '' && !window.confirm('Заменить текущее описание?')) {
			return;
		}

		descriptionInput.value = description;
		descriptionInput.dispatchEvent(new Event('input', {bubbles: true}));
		form.dispatchEvent(new Event('change', {bubbles: true}));
		setStatus(root, 'Описание вставлено. Проверьте текст перед публикацией.', '');
	}

	function init(root) {
		var form = root.closest('form');
		var button = root.querySelector('[data-metadata-search-button]');
		var endpoint = root.getAttribute('data-metadata-endpoint') || '/api/metadata_search.php';
		var csrf = root.getAttribute('data-metadata-csrf') || '';

		if (!form || !button) {
			return;
		}

		button.addEventListener('click', function () {
			var titleInput = inputByName(form, 'name');
			var categorySelect = inputByName(form, 'catid') || inputByName(form, 'category');
			var title = text(titleInput ? titleInput.value : '');
			var body = new URLSearchParams();

			clearResults(root);
			if (!title) {
				setStatus(root, 'Введите название релиза.', 'error');
				return;
			}

			body.set('title', title);
			body.set('year', currentYear(form));
			body.set('category', optionText(categorySelect));
			body.set('type', checkedType(form));
			body.set('csrf_token', csrf);

			button.disabled = true;
			button.textContent = 'Ищу...';
			setStatus(root, 'Ищу описание в русской Wikipedia...', '');

			window.fetch(endpoint, {
				method: 'POST',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
					'X-Requested-With': 'XMLHttpRequest',
					'X-CSRF-Token': csrf
				},
				credentials: 'same-origin',
				body: body.toString()
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					if (!payload || !payload.ok) {
						throw new Error(payload && payload.message ? payload.message : 'Не удалось получить данные.');
					}

					var items = Array.isArray(payload.items) ? payload.items : [];
					if (!items.length) {
						setStatus(root, 'Русское описание не найдено. Попробуйте уточнить название или год.', '');
						return;
					}

					setStatus(root, items.length === 1 ? 'Найден 1 вариант.' : 'Найдено вариантов: ' + items.length + '.', '');
					renderResults(root, form, items);
				})
				.catch(function (error) {
					setStatus(root, error && error.message ? error.message : 'Wikipedia/Wikidata временно недоступны.', 'error');
				})
				.then(function () {
					button.disabled = false;
					button.textContent = '🔎 Найти описание';
				});
		});
	}

	ready(function () {
		var roots = document.querySelectorAll('[data-metadata-search-root]');
		for (var i = 0; i < roots.length; i++) {
			init(roots[i]);
		}
	});
})();
