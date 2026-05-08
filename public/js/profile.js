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

    function showNotice(message, isError) {
      if (!ajaxMessage) {
        return;
      }

      ajaxMessage.textContent = message || '';
      ajaxMessage.className = 'profile-inline-message profile-inline-message-' + (isError ? 'error' : 'success');
      ajaxMessage.hidden = !message;
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

    document.addEventListener('click', function (event) {
      var openButton = event.target.closest('[data-profile-open-message]');
      var closeButton = event.target.closest('[data-profile-close-message]');
      var backdrop = event.target.closest('.profile-modal-backdrop');
      var isBackdropClick = backdrop && event.target === backdrop;
      var formData;

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

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal && !modal.hidden) {
        closeModal();
      }
    });
  });
})();
