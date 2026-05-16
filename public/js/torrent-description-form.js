(function (window, document) {
	'use strict';

	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}

	function escapeAttribute(value) {
		return escapeHtml(value).replace(/"/g, '&quot;');
	}

	function fieldTypeForLabel(label) {
		return ['Описание', 'В ролях', 'Треклист', 'Системные требования'].indexOf(String(label || '').trim()) !== -1 ? 'textarea' : 'text';
	}

	window.initTorrentDescriptionForm = function (config) {
		config = config || {};

		var form = document.querySelector(config.formSelector || '');
		var categorySelect = document.querySelector(config.categorySelector || '');
		var templateFieldsContainer = document.querySelector(config.templateFieldsContainerSelector || '');
		var descriptionField = document.querySelector(config.descriptionSelector || '');
		var typeOptionsContainer = document.querySelector(config.typeSelector || '');
		var templates = config.templates || {};
		var templateExamples = config.examples || {};
		var typeOptions = config.typeOptions || {};
		var defaultTemplateKey = config.defaultTemplateKey || 'movies';
		var fieldIdPrefix = config.fieldIdPrefix || 'torrent_template_';

		if (!form || !categorySelect || !templateFieldsContainer || !descriptionField || !typeOptionsContainer) {
			return;
		}

		function selectedCategoryTemplateKey() {
			var option = categorySelect.options[categorySelect.selectedIndex];
			return option && option.getAttribute('data-template-key') ? option.getAttribute('data-template-key') : defaultTemplateKey;
		}

		function selectedTexts(selector) {
			var nodes = form.querySelectorAll(selector);
			var result = [];

			Array.prototype.forEach.call(nodes, function (node) {
				var label = node.parentNode ? node.parentNode.querySelector('span') : null;
				var text = label ? String(label.textContent || '').trim() : '';
				if (text !== '') {
					result.push(text);
				}
			});

			return result;
		}

		function selectedRadioText(name) {
			var input = form.querySelector('input[name="' + name + '"]:checked');
			if (!input || !input.parentNode) {
				return '';
			}

			var label = input.parentNode.querySelector('span');
			return label ? String(label.textContent || '').trim() : '';
		}

		function currentTypeOptions() {
			return typeOptions[selectedCategoryTemplateKey()] || {};
		}

		function currentTemplateExamples() {
			return templateExamples[selectedCategoryTemplateKey()] || templateExamples[defaultTemplateKey] || {};
		}

		function templateItems() {
			var templateKey = selectedCategoryTemplateKey();
			var template = templates[templateKey] || templates[defaultTemplateKey] || { items: [] };
			return Array.isArray(template.items) ? template.items : [];
		}

		function collectTemplateValues() {
			var values = {};
			var nodes = templateFieldsContainer.querySelectorAll('[data-template-label]');

			Array.prototype.forEach.call(nodes, function (node) {
				var label = String(node.getAttribute('data-template-label') || '').trim();
				var input = node.querySelector('input, textarea');
				if (!label || !input) {
					return;
				}

				values[label] = String(input.value || '');
			});

			return values;
		}

		function renderTypeOptions() {
			var options = currentTypeOptions();
			var currentInput = form.querySelector('input[name="content_type"]:checked');
			var currentValue = currentInput ? String(currentInput.value || '') : '';
			var html = '';

			Object.keys(options).forEach(function (value, index) {
				var label = String(options[value] || '').trim();
				var checked = '';

				if ((currentValue !== '' && currentValue === value) || (currentValue === '' && index === 0)) {
					checked = ' checked';
				}

				html += '<label class="upload-option">'
					+ '<input type="radio" name="content_type" value="' + value.replace(/"/g, '&quot;') + '"' + checked + '>'
					+ '<span>' + label.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>'
					+ '</label>';
			});

			typeOptionsContainer.innerHTML = html;
		}

		function renderTemplateFields() {
			var values = collectTemplateValues();
			var examples = currentTemplateExamples();
			var html = '';

			templateItems().forEach(function (item) {
				var itemType = String(item.type || 'field');
				var label = String(item.label || '').trim();
				var auto = String(item.auto || '').trim();
				var fieldType = fieldTypeForLabel(label);
				var value = String(values[label] || '');
				var example = String(examples[label] || '');
				var fieldId = fieldIdPrefix + label.toLowerCase().replace(/[^a-zа-я0-9]+/gi, '_');

				if (!label || itemType === 'section' || auto !== '') {
					return;
				}

				html += '<div class="edit-template-field' + (fieldType === 'textarea' ? ' edit-template-field-full' : '') + '" data-template-label="' + escapeAttribute(label) + '" data-template-type="' + fieldType + '">';
				html += '<label class="upload-label" for="' + fieldId + '">' + escapeHtml(label) + '</label>';
				if (fieldType === 'textarea') {
					html += '<textarea id="' + fieldId + '" class="upload-textarea edit-template-textarea" name="template_values[' + escapeAttribute(label) + ']" placeholder="' + escapeAttribute(example) + '">' + escapeHtml(value) + '</textarea>';
				} else {
					html += '<input id="' + fieldId + '" class="upload-input" type="text" name="template_values[' + escapeAttribute(label) + ']" value="' + escapeAttribute(value) + '" placeholder="' + escapeAttribute(example) + '">';
				}
				if (example !== '') {
					html += '<div class="upload-example-hint">Например: ' + escapeHtml(example).replace(/\n/g, '<br>') + '</div>';
				}
				html += '</div>';
			});

			templateFieldsContainer.innerHTML = html;
		}

		function buildDescription() {
			var currentValues = collectTemplateValues();
			var autoValues = {
				type: selectedRadioText('content_type'),
				genre: selectedTexts('input[name="genre[]"]:checked').join(', '),
				language: selectedTexts('input[name="language[]"]:checked').join(', '),
				subtitles: selectedTexts('input[name="subtitles[]"]:checked').join(', '),
				country: selectedTexts('input[name="country[]"]:checked').join(', ')
			};
			var lines = [];

			templateItems().forEach(function (item) {
				var itemType = String(item.type || 'field');
				var label = String(item.label || '').trim();
				var value = '';

				if (!label) {
					return;
				}

				if (itemType === 'section') {
					if (lines.length > 0 && lines[lines.length - 1] !== '') {
						lines.push('');
					}
					lines.push('[u]' + label + '[/u]');
					return;
				}

				if (item.auto && typeof autoValues[item.auto] !== 'undefined' && autoValues[item.auto] !== '') {
					value = autoValues[item.auto];
				} else {
					value = String(currentValues[label] || '').trim();
				}

				if (value.indexOf('\n') !== -1) {
					lines.push('[b]' + label + ':[/b]' + (value !== '' ? '\n' + value : ''));
					return;
				}

				lines.push('[b]' + label + ':[/b]' + (value !== '' ? ' ' + value : ''));
			});

			return lines.join('\n');
		}

		function syncDescription() {
			descriptionField.value = buildDescription();
		}

		categorySelect.addEventListener('change', function () {
			renderTypeOptions();
			renderTemplateFields();
			syncDescription();
		});

		templateFieldsContainer.addEventListener('input', function () {
			syncDescription();
		});

		form.addEventListener('change', function (event) {
			var target = event.target;
			if (!target || !target.name) {
				return;
			}

			if (target.name === 'content_type' || target.name === 'genre[]' || target.name === 'language[]' || target.name === 'subtitles[]' || target.name === 'country[]') {
				syncDescription();
			}
		});

		form.addEventListener('submit', function () {
			syncDescription();
		});

		renderTypeOptions();
		if (config.initialRenderFields) {
			renderTemplateFields();
		}
		syncDescription();
	};
})(window, document);
