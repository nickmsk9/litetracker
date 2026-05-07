(function () {
  var reactionCache = {};
  var emojiList = ['😀','😁','😂','🤣','😊','😍','😘','🤔','😎','👍','👎','👏','🔥','❤️','💙','💛','💜','🎉','✨','🤝','🙏','😴','🤯','😡','🥳','🤖','🎬','🎵','📚','💡'];

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
      toggle.type = 'button';
      toggle.className = 'wall-comment-button lt-emoji-toggle';
      toggle.setAttribute('data-emoji-toggle', '1');
      toggle.textContent = '🙂 Смайлы';

      var panel = document.createElement('div');
      panel.className = 'lt-emoji-panel';
      panel.setAttribute('data-emoji-panel', '1');
      panel.hidden = true;
      panel.innerHTML = emojiList.map(function (emoji) {
        return '<button type="button" class="lt-emoji-item" data-emoji-value="' + emoji + '">' + emoji + '</button>';
      }).join('');

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
      var emojiItem = closest(event.target, '[data-emoji-value]');

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
          panel.hidden = !panel.hidden;
        }
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
          }
        }
        return;
      }

      if (modalClose) {
        event.preventDefault();
        hideModal(benefitsModal);
        hideModal(reactionModal);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        hideModal(benefitsModal);
        hideModal(reactionModal);
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
})();
