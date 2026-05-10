(function () {
	'use strict';

	function ready(callback) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback);
			return;
		}

		callback();
	}

	function splitValue(value) {
		var parts = String(value || '').split(/[,;]+/);
		var tail = parts.length ? parts[parts.length - 1] : '';
		return {
			parts: parts,
			query: String(tail || '').trim()
		};
	}

	function hasTag(value, tag) {
		var wanted = String(tag || '').trim().toLowerCase();
		var parts = String(value || '').split(/[,;]+/);

		for (var i = 0; i < parts.length; i++) {
			if (String(parts[i] || '').trim().toLowerCase() === wanted) {
				return true;
			}
		}

		return false;
	}

	function applyTag(input, tag) {
		var value = String(input.value || '');
		var parsed = splitValue(value);
		var parts = parsed.parts;

		if (hasTag(value, tag)) {
			input.focus();
			return;
		}

		if (parts.length) {
			parts[parts.length - 1] = ' ' + tag;
		} else {
			parts = [tag];
		}

		input.value = parts
			.map(function (part) {
				return String(part || '').trim();
			})
			.filter(Boolean)
			.join(', ');

		input.dispatchEvent(new Event('input', {bubbles: true}));
		input.focus();
	}

	function initInput(input) {
		var endpoint = input.getAttribute('data-tags-suggest-url') || '/api/tags_suggest.php';
		var dropdown = document.createElement('div');
		var timer = 0;
		var requestId = 0;

		dropdown.className = 'tags-suggest-box';
		dropdown.setAttribute('role', 'listbox');
		input.insertAdjacentElement('afterend', dropdown);

		function close() {
			dropdown.classList.remove('is-open');
			dropdown.innerHTML = '';
		}

		function render(items) {
			dropdown.innerHTML = '';

			if (!items || !items.length) {
				close();
				return;
			}

			items.forEach(function (item) {
				var name = String(item.name || '').trim();
				if (!name) {
					return;
				}

				var button = document.createElement('button');
				var label = document.createElement('span');
				var count = document.createElement('span');

				button.type = 'button';
				button.className = 'tags-suggest-item';
				button.setAttribute('role', 'option');
				label.className = 'tags-suggest-name';
				label.textContent = name;
				button.appendChild(label);

				if (item.count) {
					count.className = 'tags-suggest-count';
					count.textContent = String(item.count);
					button.appendChild(count);
				}

				button.addEventListener('mousedown', function (event) {
					event.preventDefault();
					applyTag(input, name);
					close();
				});

				dropdown.appendChild(button);
			});

			if (dropdown.children.length) {
				dropdown.classList.add('is-open');
			} else {
				close();
			}
		}

		function load() {
			var query = splitValue(input.value).query;
			requestId++;
			var currentRequest = requestId;

			if (query.length < 2 || typeof window.fetch !== 'function') {
				close();
				return;
			}

			window.fetch(endpoint + '?q=' + encodeURIComponent(query), {
				headers: {
					'Accept': 'application/json',
					'X-Requested-With': 'XMLHttpRequest'
				},
				credentials: 'same-origin'
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					if (currentRequest !== requestId || !payload || !payload.ok) {
						return;
					}

					render(payload.items || []);
				})
				.catch(function () {
					if (currentRequest === requestId) {
						close();
					}
				});
		}

		input.addEventListener('input', function () {
			window.clearTimeout(timer);
			timer = window.setTimeout(load, 180);
		});

		input.addEventListener('blur', function () {
			window.setTimeout(close, 120);
		});

		input.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				close();
			}
		});
	}

	ready(function () {
		var inputs = document.querySelectorAll('[data-tags-suggest]');

		for (var i = 0; i < inputs.length; i++) {
			initInput(inputs[i]);
		}
	});
})();
