(function () {
  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
      return;
    }

    fn();
  }

  function sendRequest(formData, onSuccess, onError) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax/profile.php', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function () {
      var payload;

      if (xhr.readyState !== 4) {
        return;
      }

      try {
        payload = JSON.parse(xhr.responseText || '{}');
      } catch (error) {
        if (typeof onError === 'function') {
          onError('Не удалось обработать ответ сервера.');
        }
        return;
      }

      if (xhr.status >= 200 && xhr.status < 300 && payload && payload.ok) {
        if (typeof onSuccess === 'function') {
          onSuccess(payload);
        }
        return;
      }

      if (typeof onError === 'function') {
        onError((payload && payload.message) ? payload.message : 'Произошла ошибка.');
      }
    };

    xhr.send(formData);
  }

  ready(function () {
    var page = document.querySelector('[data-profile-page]');
    if (!page) {
      return;
    }

    var ajaxMessage = document.getElementById('profile-ajax-message');
    var modal = document.getElementById('profile-message-modal');
    var modalTextarea = document.getElementById('profile-message-text');
    var modalForm = document.getElementById('profile-message-form');
    var blacklistButton = document.querySelector('[data-profile-toggle-blacklist]');
    var editorPanel = document.getElementById('profile-editor-panel');
    var editorForm = document.getElementById('profile-editor-form');
    var editorToggle = document.querySelector('[data-profile-toggle-editor]');

    function showNotice(message, isError) {
      if (!ajaxMessage) {
        return;
      }

      ajaxMessage.textContent = message || '';
      ajaxMessage.className = 'profile-inline-message profile-inline-message-' + (isError ? 'error' : 'success');
      ajaxMessage.hidden = !message;
    }

    function escapeHtml(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function setText(selector, value) {
      var element = document.querySelector(selector);
      if (element) {
        element.textContent = value || '';
      }
    }

    function setHtml(selector, value) {
      var element = document.querySelector(selector);
      if (element) {
        element.innerHTML = value || '';
      }
    }

    function openEditor() {
      if (!editorPanel) {
        return;
      }

      editorPanel.hidden = false;
      editorPanel.removeAttribute('hidden');
      if (editorToggle) {
        editorToggle.setAttribute('aria-expanded', 'true');
      }
      if (typeof editorPanel.scrollIntoView === 'function') {
        try {
          editorPanel.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } catch (error) {
          editorPanel.scrollIntoView();
        }
      }
    }

    function closeEditor() {
      if (!editorPanel) {
        return;
      }

      editorPanel.hidden = true;
      editorPanel.setAttribute('hidden', 'hidden');
      if (editorToggle) {
        editorToggle.setAttribute('aria-expanded', 'false');
      }
    }

    function appendHistory(payload) {
      var notes = payload && payload.history_notes;
      var history = document.querySelector('[data-profile-editor-history]');
      var html = '';

      if (!history || !notes || !notes.length) {
        return;
      }

      for (var i = 0; i < notes.length; i++) {
        html += '<div class="settings-history-item">'
          + '<div class="settings-history-meta">' + escapeHtml(payload.history_date || '') + ' · admin #' + escapeHtml(payload.history_admin || '') + '</div>'
          + '<div class="settings-history-text">' + escapeHtml(notes[i]).replace(/\n/g, '<br>') + '</div>'
          + '</div>';
      }

      history.insertAdjacentHTML('afterbegin', html);
    }

    function openModal() {
      if (!modal) {
        return;
      }

      modal.hidden = false;
      document.body.classList.add('profile-modal-open');

      if (modalTextarea) {
        modalTextarea.focus();
      }
    }

    function closeModal() {
      if (!modal) {
        return;
      }

      modal.hidden = true;
      document.body.classList.remove('profile-modal-open');
    }

    function loadProfileTab(href, pushState) {
      var currentPage = document.querySelector('[data-profile-page]');
      var currentPrimary = currentPage ? currentPage.querySelector('.profile-primary') : null;
      var currentSidebar = currentPage ? currentPage.querySelector('.profile-sidebar') : null;

      if (!currentPage || !currentPrimary || !currentSidebar || !href) {
        window.location.href = href;
        return;
      }

      fetch(href, {
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html'
        }
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Request failed');
          }

          return response.text();
        })
        .then(function (html) {
          var doc = new DOMParser().parseFromString(html, 'text/html');
          var nextPage = doc.querySelector('[data-profile-page]');
          var nextPrimary = nextPage ? nextPage.querySelector('.profile-primary') : null;
          var nextSidebar = nextPage ? nextPage.querySelector('.profile-sidebar') : null;

          if (!nextPrimary || !nextSidebar) {
            throw new Error('Invalid profile response');
          }

          currentPrimary.innerHTML = nextPrimary.innerHTML;
          currentSidebar.innerHTML = nextSidebar.innerHTML;

          if (doc.title) {
            document.title = doc.title;
          }

          if (pushState && window.history && window.history.pushState) {
            window.history.pushState({ profileAjaxTab: true }, '', href);
          }
        })
        .catch(function () {
          window.location.href = href;
        });
    }

    document.addEventListener('click', function (event) {
      var profileTabLink = event.target.closest('.profile-sidebar-link');
      var openButton = event.target.closest('[data-profile-open-message]');
      var closeButton = event.target.closest('[data-profile-close-message]');
      var backdrop = event.target.closest('.profile-modal-backdrop');
      var isBackdropClick = backdrop && event.target === backdrop;
      var formData;

      if (profileTabLink && profileTabLink.href && profileTabLink.href.indexOf('profile.php') !== -1 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
        event.preventDefault();
        loadProfileTab(profileTabLink.href, true);
        return;
      }

      if (openButton) {
        event.preventDefault();
        openModal();
        return;
      }

      if (closeButton || isBackdropClick) {
        event.preventDefault();
        closeModal();
        return;
      }

      if (blacklistButton && event.target.closest('[data-profile-toggle-blacklist]')) {
        event.preventDefault();

        formData = new FormData();
        formData.append('action', 'toggle_blacklist');
        formData.append('user_id', String(blacklistButton.getAttribute('data-user-id') || '0'));

        sendRequest(formData, function (payload) {
          blacklistButton.textContent = payload.label || 'Добавить в ЧС';
          blacklistButton.setAttribute('data-blacklisted', String(payload.blacklisted || 0));
          blacklistButton.classList.toggle('profile-card-button-dark-active', String(payload.blacklisted || 0) === '1');
          showNotice(payload.message || '');
        }, function (message) {
          showNotice(message, true);
        });
      }

      if (event.target.closest('[data-profile-toggle-editor]')) {
        event.preventDefault();
        if (editorPanel && editorPanel.hidden) {
          openEditor();
        } else {
          closeEditor();
        }
        return;
      }

      if (event.target.closest('[data-profile-close-editor]')) {
        event.preventDefault();
        closeEditor();
      }
    });

    if (modalForm) {
      modalForm.addEventListener('submit', function (event) {
        var formData = new FormData(modalForm);

        event.preventDefault();
        formData.append('action', 'send_message');

        sendRequest(formData, function (payload) {
          modalForm.reset();
          closeModal();
          showNotice(payload.message || 'Сообщение отправлено.');
        }, function (message) {
          showNotice(message, true);
        });
      });
    }

    if (editorForm) {
      editorForm.addEventListener('submit', function (event) {
        var formData = new FormData(editorForm);

        event.preventDefault();
        formData.append('action', 'moderate_profile');

        sendRequest(formData, function (payload) {
          setHtml('[data-profile-display-name]', payload.display_name_html || '');
          setText('[data-profile-display-class]', payload.class_name || '');
          setText('[data-profile-display-uploaded]', payload.uploaded || '');
          setText('[data-profile-display-downloaded]', payload.downloaded || '');
          setText('[data-profile-display-bonus]', payload.bonus || '');
          appendHistory(payload);
          showNotice(payload.message || 'Изменения сохранены.');
        }, function (message) {
          showNotice(message, true);
        });
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal && !modal.hidden) {
        closeModal();
      }
    });

    window.addEventListener('popstate', function () {
      if (window.location.href.indexOf('profile.php') !== -1) {
        loadProfileTab(window.location.href, false);
      }
    });
  });
})();
