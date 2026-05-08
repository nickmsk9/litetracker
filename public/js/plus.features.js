(function () {
  var reactionCache = {};
  var emojiPanelCounter = 0;
  var maxEmojiSearchResults = 24;
  var telegramEmojiBlocks = [
    {
      id: 'faces',
      title: 'Улыбки и смайлы',
      items: [
        { value: '🙂', title: 'Спокойная улыбка', keywords: ['улыбка', 'радость', 'smile', 'telegram'] },
        { value: '😊', title: 'Тёплая улыбка', keywords: ['счастье', 'милота', 'happy'] },
        { value: '😁', title: 'Широкая улыбка', keywords: ['радость', 'happy', 'joy'] },
        { value: '😂', title: 'Слёзы радости', keywords: ['смех', 'лол', 'laugh'] },
        { value: '🤣', title: 'Кататься от смеха', keywords: ['смех', 'угар', 'rofl'] },
        { value: '😉', title: 'Подмигивание', keywords: ['флирт', 'wink'] },
        { value: '😎', title: 'Крутой', keywords: ['cool', 'очки', 'style'] },
        { value: '🥹', title: 'До слёз', keywords: ['трогательно', 'милота', 'cute'] },
        { value: '🤔', title: 'Задумчивость', keywords: ['думать', 'question', 'think'] },
        { value: '🫠', title: 'Растаял', keywords: ['неловко', 'melting', 'awkward'] },
        { value: '🥳', title: 'Праздник', keywords: ['party', 'пати', 'веселье'] },
        { value: '🤯', title: 'Взорван мозг', keywords: ['wow', 'mindblown', 'шок'] }
      ]
    },
    {
      id: 'hands',
      title: 'Жесты',
      items: [
        { value: '👍', title: 'Лайк', keywords: ['ok', 'good', 'палец вверх'] },
        { value: '👎', title: 'Дизлайк', keywords: ['нет', 'палец вниз', 'bad'] },
        { value: '👏', title: 'Аплодисменты', keywords: ['clap', 'браво'] },
        { value: '🙌', title: 'Ура', keywords: ['celebrate', 'yay', 'победа'] },
        { value: '🤝', title: 'Рукопожатие', keywords: ['сделка', 'договор', 'team'] },
        { value: '🙏', title: 'Спасибо', keywords: ['please', 'благодарность', 'pray'] },
        { value: '🫶', title: 'Сердце руками', keywords: ['любовь', 'сердце', 'care'] },
        { value: '👌', title: 'Всё ок', keywords: ['окей', 'perfect'] },
        { value: '🤌', title: 'Идеально', keywords: ['italy', 'gesture', 'вкусно'] },
        { value: '✌️', title: 'Победа', keywords: ['peace', 'victory'] },
        { value: '🤙', title: 'На связи', keywords: ['call me', 'звонок'] },
        { value: '🫡', title: 'С уважением', keywords: ['salute', 'уважение'] }
      ]
    },
    {
      id: 'hearts',
      title: 'Сердца и огонь',
      items: [
        { value: '❤️', title: 'Красное сердце', keywords: ['love', 'сердце'] },
        { value: '🩷', title: 'Розовое сердце', keywords: ['pink', 'романтика'] },
        { value: '🧡', title: 'Оранжевое сердце', keywords: ['orange', 'тепло'] },
        { value: '💛', title: 'Жёлтое сердце', keywords: ['yellow', 'friendship'] },
        { value: '💚', title: 'Зелёное сердце', keywords: ['green', 'eco'] },
        { value: '💙', title: 'Синее сердце', keywords: ['blue', 'спокойствие'] },
        { value: '💜', title: 'Фиолетовое сердце', keywords: ['purple', 'dream'] },
        { value: '🖤', title: 'Чёрное сердце', keywords: ['black', 'dark'] },
        { value: '🤍', title: 'Белое сердце', keywords: ['white', 'light'] },
        { value: '🔥', title: 'Огонь', keywords: ['hot', 'lit', 'fire'] },
        { value: '✨', title: 'Искры', keywords: ['sparkles', 'magic'] },
        { value: '💯', title: 'Сто из ста', keywords: ['100', 'идеально', 'top'] }
      ]
    },
    {
      id: 'life',
      title: 'Настроение и жизнь',
      items: [
        { value: '🎉', title: 'Конфетти', keywords: ['celebration', 'ура', 'party'] },
        { value: '🎵', title: 'Музыка', keywords: ['music', 'song', 'трек'] },
        { value: '🎬', title: 'Кино', keywords: ['movie', 'film'] },
        { value: '📚', title: 'Книги', keywords: ['book', 'study', 'читать'] },
        { value: '💡', title: 'Идея', keywords: ['idea', 'лампочка'] },
        { value: '🚀', title: 'Ракета', keywords: ['launch', 'рост', 'rocket'] },
        { value: '🌙', title: 'Ночь', keywords: ['moon', 'sleep', 'ночь'] },
        { value: '☕', title: 'Кофе', keywords: ['coffee', 'утро'] },
        { value: '🍕', title: 'Пицца', keywords: ['food', 'еда'] },
        { value: '🍿', title: 'Попкорн', keywords: ['snack', 'movie'] },
        { value: '🎮', title: 'Гейминг', keywords: ['game', 'игры'] },
        { value: '🤖', title: 'Робот', keywords: ['ai', 'bot', 'технологии'] }
      ]
    }
  ];
  var telegramStickerBlocks = [
    {
      id: 'moods',
      title: 'Стикеры-настроения',
      items: [
        { value: '¯\\_(ツ)_/¯', title: 'Ну бывает', keywords: ['стикер', 'не знаю', 'shrug'] },
        { value: '(づ｡◕‿‿◕｡)づ', title: 'Обнимашки', keywords: ['hug', 'обнять', 'тепло'] },
        { value: '༼ つ ◕_◕ ༽つ', title: 'Поддержка', keywords: ['support', 'забота', 'care'] },
        { value: 'ᕕ( ᐛ )ᕗ', title: 'Побежали', keywords: ['run', 'go', 'энергия'] },
        { value: 'ಠ_ಠ', title: 'Осуждение', keywords: ['look', 'serious', 'недоверие'] },
        { value: '（╯°□°）╯︵ ┻━┻', title: 'Переворот стола', keywords: ['rage', 'стол', 'angry'] }
      ]
    },
    {
      id: 'cute',
      title: 'Милые стикеры',
      items: [
        { value: 'ʕ•ᴥ•ʔ', title: 'Медвежонок', keywords: ['bear', 'милый', 'cute'] },
        { value: '🐸☕', title: 'Лягушка с кофе', keywords: ['frog', 'coffee', 'meme'] },
        { value: '😼✨', title: 'Котик-звезда', keywords: ['cat', 'кот', 'glam'] },
        { value: '🫶✨', title: 'Сердечки', keywords: ['love', 'shine', 'милота'] },
        { value: '😴💤', title: 'Сплю', keywords: ['sleep', 'ночь', 'устал'] },
        { value: '🥺👉👈', title: 'Смущение', keywords: ['please', 'cute', 'просить'] }
      ]
    },
    {
      id: 'party',
      title: 'Праздничные стикеры',
      items: [
        { value: '🪩🕺', title: 'Танцы', keywords: ['dance', 'party', 'диско'] },
        { value: '🎧😎', title: 'Врубай музыку', keywords: ['dj', 'music', 'beat'] },
        { value: '🚀✨', title: 'Полетели', keywords: ['launch', 'wow', 'rocket'] },
        { value: '🔥😼🔥', title: 'Очень мощно', keywords: ['fire', 'top', 'круто'] },
        { value: '🎉🥳', title: 'Празднуем', keywords: ['celebrate', 'ура', 'party'] },
        { value: '💯⚡', title: 'Максимум', keywords: ['energy', '100', 'power'] }
      ]
    }
  ];
  var telegramCatalog = [];

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
      return;
    }
    fn();
  }

  function closest(el, selector) {
    return el && el.closest ? el.closest(selector) : null;
  }

  function insertAtCursor(textarea, text) {
    var start = textarea.selectionStart || 0;
    var end = textarea.selectionEnd || 0;
    var value = textarea.value || '';
    textarea.value = value.slice(0, start) + text + value.slice(end);
    textarea.focus();
    if (typeof textarea.setSelectionRange === 'function') {
      var cursor = start + text.length;
      textarea.setSelectionRange(cursor, cursor);
    }
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function normalizeSearchText(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/\s+/g, ' ')
      .trim();
  }

  function shouldHideEmojiEmptyState(panel) {
    return panel.querySelectorAll('[data-emoji-kind-toggle][aria-pressed="true"]').length > 0;
  }

  function buildCatalog() {
    function pushItems(blocks, kind) {
      Array.prototype.forEach.call(blocks, function (block) {
        Array.prototype.forEach.call(block.items, function (item) {
          telegramCatalog.push({
            kind: kind,
            groupTitle: block.title,
            value: item.value,
            title: item.title,
            keywords: item.keywords || [],
            searchText: normalizeSearchText([
              item.title,
              block.title,
              kind === 'sticker' ? 'стикер stickers telegram' : 'эмодзи emoji telegram',
              item.value
            ].concat(item.keywords || []).join(' '))
          });
        });
      });
    }

    pushItems(telegramEmojiBlocks, 'emoji');
    pushItems(telegramStickerBlocks, 'sticker');
  }

  function renderEmojiButton(item) {
    var buttonClass = 'lt-emoji-item' + (item.kind === 'sticker' ? ' lt-emoji-item-sticker' : '');
    return '<button type="button" class="' + buttonClass + '" data-emoji-value="' + escapeHtml(item.value) + '" title="' + escapeHtml(item.title) + '">' +
      '<span class="lt-emoji-item-symbol">' + escapeHtml(item.value) + '</span>' +
      '<span class="lt-emoji-item-name">' + escapeHtml(item.title) + '</span>' +
      '</button>';
  }

  function renderEmojiBlock(blocks, kind) {
    return blocks.map(function (block) {
      return '<section class="lt-emoji-group">' +
        '<div class="lt-emoji-group-title">' + escapeHtml(block.title) + '</div>' +
        '<div class="lt-emoji-grid">' +
        block.items.map(function (item) {
          return renderEmojiButton({
            kind: kind,
            value: item.value,
            title: item.title
          });
        }).join('') +
        '</div>' +
      '</section>';
    }).join('');
  }

  function renderEmojiPanelMarkup(panelId) {
    return '' +
      '<div class="lt-emoji-panel-head">' +
        '<div>' +
          '<div class="lt-emoji-panel-title">Telegram Emoji</div>' +
          '<div class="lt-emoji-panel-subtitle">Поиск по Telegram-эмодзи и стикерам</div>' +
        '</div>' +
        '<div class="lt-emoji-panel-badge">Telegram</div>' +
      '</div>' +
      '<label class="lt-emoji-searchbox" for="' + escapeHtml(panelId + '-search') + '">' +
        '<span class="lt-emoji-search-icon">🔎</span>' +
        '<input id="' + escapeHtml(panelId + '-search') + '" class="lt-emoji-search-input" type="search" autocomplete="off" spellcheck="false" placeholder="Искать Telegram-эмодзи и стикеры" data-emoji-search="1">' +
      '</label>' +
      '<div class="lt-emoji-filter-row">' +
        '<button type="button" class="lt-emoji-kind-button" data-emoji-kind-toggle="emoji" aria-pressed="false">Эмодзи</button>' +
        '<button type="button" class="lt-emoji-kind-button" data-emoji-kind-toggle="sticker" aria-pressed="false">Стикеры</button>' +
      '</div>' +
      '<div class="lt-emoji-empty-state" data-emoji-initial-state="1">Выберите категорию или начните поиск эмодзи.</div>' +
      '<section class="lt-emoji-results" data-emoji-search-results hidden>' +
        '<div class="lt-emoji-group-title">Результаты поиска</div>' +
        '<div class="lt-emoji-results-limit" data-emoji-results-limit hidden></div>' +
        '<div class="lt-emoji-grid" data-emoji-result-list></div>' +
        '<div class="lt-emoji-empty-state" data-emoji-empty hidden>Ничего не найдено. Попробуйте другое слово.</div>' +
      '</section>' +
      '<section class="lt-emoji-block" data-emoji-block="emoji" hidden>' +
        renderEmojiBlock(telegramEmojiBlocks, 'emoji') +
      '</section>' +
      '<section class="lt-emoji-block" data-emoji-block="sticker" hidden>' +
        renderEmojiBlock(telegramStickerBlocks, 'sticker') +
      '</section>';
  }

  function setEmojiPanelDefaults(panel) {
    var searchInput;
    var toggleButtons;
    var blocks;
    var results;
    var emptyState;
    var initialState;

    if (!panel) {
      return;
    }

    searchInput = panel.querySelector('[data-emoji-search]');
    toggleButtons = panel.querySelectorAll('[data-emoji-kind-toggle]');
    blocks = panel.querySelectorAll('[data-emoji-block]');
    results = panel.querySelector('[data-emoji-search-results]');
    emptyState = panel.querySelector('[data-emoji-empty]');
    initialState = panel.querySelector('[data-emoji-initial-state]');

    if (searchInput) {
      searchInput.value = '';
    }

    Array.prototype.forEach.call(toggleButtons, function (button) {
      button.setAttribute('aria-pressed', 'false');
    });

    Array.prototype.forEach.call(blocks, function (block) {
      block.hidden = true;
    });

    if (results) {
      results.hidden = true;
    }

    if (emptyState) {
      emptyState.hidden = true;
    }

    if (initialState) {
      initialState.hidden = false;
    }
  }

  function closeAllEmojiPanels(exceptPanel) {
    var panels = document.querySelectorAll('[data-emoji-panel]');
    Array.prototype.forEach.call(panels, function (panel) {
      if (exceptPanel && panel === exceptPanel) {
        return;
      }
      panel.hidden = true;
    });
    Array.prototype.forEach.call(document.querySelectorAll('[data-emoji-toggle]'), function (button) {
      var form = closest(button, '[data-comment-form], #profile-wall-form');
      var panel = form ? form.querySelector('[data-emoji-panel]') : null;
      button.setAttribute('aria-expanded', panel && !panel.hidden ? 'true' : 'false');
    });
  }

  function toggleEmojiBlock(panel, kind) {
    var button = panel ? panel.querySelector('[data-emoji-kind-toggle="' + kind + '"]') : null;
    var block = panel ? panel.querySelector('[data-emoji-block="' + kind + '"]') : null;
    var initialState = panel ? panel.querySelector('[data-emoji-initial-state]') : null;
    var isActive;

    if (!button || !block) {
      return;
    }

    isActive = button.getAttribute('aria-pressed') === 'true';
    button.setAttribute('aria-pressed', isActive ? 'false' : 'true');
    block.hidden = isActive;

    if (initialState) {
      var searchInput = panel.querySelector('[data-emoji-search]');
      initialState.hidden = shouldHideEmojiEmptyState(panel) || !!(searchInput && normalizeSearchText(searchInput.value));
    }
  }

  function runEmojiSearch(panel) {
    var searchInput = panel ? panel.querySelector('[data-emoji-search]') : null;
    var query = normalizeSearchText(searchInput ? searchInput.value : '');
    var resultsSection = panel ? panel.querySelector('[data-emoji-search-results]') : null;
    var resultList = panel ? panel.querySelector('[data-emoji-result-list]') : null;
    var limitNote = panel ? panel.querySelector('[data-emoji-results-limit]') : null;
    var emptyState = panel ? panel.querySelector('[data-emoji-empty]') : null;
    var initialState = panel ? panel.querySelector('[data-emoji-initial-state]') : null;
    var matches;
    var totalMatches;

    if (!resultsSection || !resultList || !limitNote || !emptyState || !initialState) {
      return;
    }

    if (!query) {
      resultsSection.hidden = true;
      resultList.innerHTML = '';
      limitNote.hidden = true;
      limitNote.textContent = '';
      emptyState.hidden = true;
      initialState.hidden = shouldHideEmojiEmptyState(panel);
      return;
    }

    matches = telegramCatalog.filter(function (item) {
      return item.searchText.indexOf(query) !== -1;
    });
    totalMatches = matches.length;
    matches = matches.slice(0, maxEmojiSearchResults);

    resultsSection.hidden = false;
    resultList.innerHTML = matches.map(renderEmojiButton).join('');
    limitNote.hidden = totalMatches <= maxEmojiSearchResults;
    limitNote.textContent = totalMatches > maxEmojiSearchResults ? 'Показаны первые ' + maxEmojiSearchResults + ' результатов.' : '';
    emptyState.hidden = matches.length > 0;
    initialState.hidden = true;
  }

  function hideModal(modal) {
    if (!modal) {
      return;
    }
    modal.hidden = true;
    document.body.classList.remove('lt-modal-open');
  }

  function showModal(modal) {
    if (!modal) {
      return;
    }
    modal.hidden = false;
    document.body.classList.add('lt-modal-open');
  }

  function fetchReactionList(objectType, objectId, callback) {
    var key = objectType + ':' + objectId;
    if (reactionCache[key]) {
      callback(reactionCache[key]);
      return;
    }

    fetch('ajax/reactions.php?object_type=' + encodeURIComponent(objectType) + '&object_id=' + encodeURIComponent(objectId), {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
      .then(function (response) { return response.json(); })
      .then(function (payload) {
        var html = (payload && payload.ok && payload.html) ? payload.html : '<div class="profile-empty-state">Не удалось загрузить список.</div>';
        reactionCache[key] = html;
        callback(html);
      })
      .catch(function () {
        callback('<div class="profile-empty-state">Не удалось загрузить список.</div>');
      });
  }

  function initEmojiTools(root) {
    var forms = (root || document).querySelectorAll('[data-comment-form], #profile-wall-form');
    Array.prototype.forEach.call(forms, function (form) {
      if (form.getAttribute('data-emoji-ready') === '1') {
        return;
      }

      var textarea = form.querySelector('[data-comment-textarea], textarea[name="text"], textarea[name="descr"]');
      if (!textarea) {
        return;
      }

      form.setAttribute('data-emoji-ready', '1');

      var wrapper = document.createElement('div');
      wrapper.className = 'lt-emoji-toolbar';

      var toggle = document.createElement('button');
      var panelId = 'lt-emoji-panel-' + (++emojiPanelCounter);
      toggle.type = 'button';
      toggle.className = 'wall-comment-button lt-emoji-toggle';
      toggle.setAttribute('data-emoji-toggle', '1');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-controls', panelId);
      toggle.textContent = '💬 Telegram-смайлы';

      var panel = document.createElement('div');
      panel.id = panelId;
      panel.className = 'lt-emoji-panel';
      panel.setAttribute('data-emoji-panel', '1');
      panel.hidden = true;
      panel.innerHTML = renderEmojiPanelMarkup(panelId);

      wrapper.appendChild(toggle);
      wrapper.appendChild(panel);
      textarea.parentNode.insertBefore(wrapper, textarea);
    });
  }

  ready(function () {
    var benefitsModal = document.querySelector('[data-plus-benefits-modal]');
    var reactionModal = document.querySelector('[data-reaction-modal]');
    var reactionBody = reactionModal ? reactionModal.querySelector('[data-reaction-modal-body]') : null;

    initEmojiTools(document);

    document.addEventListener('click', function (event) {
      var benefitsOpen = closest(event.target, '[data-plus-benefits-open]');
      var modalClose = closest(event.target, '[data-lt-modal-close]');
      var reactionLink = closest(event.target, '[data-plus-reaction-list]');
      var emojiToggle = closest(event.target, '[data-emoji-toggle]');
      var emojiKindToggle = closest(event.target, '[data-emoji-kind-toggle]');
      var emojiItem = closest(event.target, '[data-emoji-value]');
      var emojiToolbar = closest(event.target, '.lt-emoji-toolbar');

      if (benefitsOpen) {
        event.preventDefault();
        showModal(benefitsModal);
        return;
      }

      if (reactionLink) {
        var objectType = reactionLink.getAttribute('data-reaction-object-type') || '';
        var objectId = reactionLink.getAttribute('data-reaction-object-id') || '0';
        if (objectType && Number(objectId) > 0) {
          event.preventDefault();
          if (reactionBody) {
            reactionBody.innerHTML = '<div class="profile-empty-state">Загрузка...</div>';
          }
          showModal(reactionModal);
          fetchReactionList(objectType, objectId, function (html) {
            if (reactionBody) {
              reactionBody.innerHTML = html;
            }
          });
        }
        return;
      }

      if (emojiToggle) {
        event.preventDefault();
        var form = closest(emojiToggle, '[data-comment-form], #profile-wall-form');
        var panel = form ? form.querySelector('[data-emoji-panel]') : null;
        if (panel) {
          if (panel.hidden) {
            closeAllEmojiPanels(panel);
            setEmojiPanelDefaults(panel);
            panel.hidden = false;
            emojiToggle.setAttribute('aria-expanded', 'true');
            var searchInput = panel.querySelector('[data-emoji-search]');
            if (searchInput) {
              searchInput.focus();
            }
          } else {
            panel.hidden = true;
            emojiToggle.setAttribute('aria-expanded', 'false');
          }
        }
        return;
      }

      if (emojiKindToggle) {
        event.preventDefault();
        toggleEmojiBlock(closest(emojiKindToggle, '[data-emoji-panel]'), emojiKindToggle.getAttribute('data-emoji-kind-toggle') || '');
        return;
      }

      if (emojiItem) {
        event.preventDefault();
        var parentForm = closest(emojiItem, '[data-comment-form], #profile-wall-form');
        var textarea = parentForm ? parentForm.querySelector('[data-comment-textarea], textarea[name="text"], textarea[name="descr"]') : null;
        if (textarea) {
          insertAtCursor(textarea, emojiItem.getAttribute('data-emoji-value') || '');
          var itemPanel = closest(emojiItem, '[data-emoji-panel]');
          if (itemPanel) {
            itemPanel.hidden = true;
            var itemToggle = parentForm ? parentForm.querySelector('[data-emoji-toggle]') : null;
            if (itemToggle) {
              itemToggle.setAttribute('aria-expanded', 'false');
            }
          }
        }
        return;
      }

      if (modalClose) {
        event.preventDefault();
        hideModal(benefitsModal);
        hideModal(reactionModal);
        closeAllEmojiPanels();
        return;
      }

      if (!emojiToolbar) {
        closeAllEmojiPanels();
      }
    });

    document.addEventListener('input', function (event) {
      var searchField = closest(event.target, '[data-emoji-search]');
      if (!searchField) {
        return;
      }
      runEmojiSearch(closest(searchField, '[data-emoji-panel]'));
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        hideModal(benefitsModal);
        hideModal(reactionModal);
        closeAllEmojiPanels();
      }
    });

    var reactionLinks = document.querySelectorAll('[data-plus-reaction-list]');
    Array.prototype.slice.call(reactionLinks, 0, 8).forEach(function (link) {
      var objectType = link.getAttribute('data-reaction-object-type') || '';
      var objectId = link.getAttribute('data-reaction-object-id') || '0';
      if (!objectType || Number(objectId) <= 0) {
        return;
      }
      fetchReactionList(objectType, objectId, function () {});
    });
  });

  buildCatalog();
})();
