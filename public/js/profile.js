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
    var wallContainer = document.getElementById('profile-wall-comments');
    var wallForm = document.getElementById('profile-wall-form');
    var wallTextarea = document.getElementById('profile-wall-text');
    var wallParentInput = document.getElementById('profile-wall-parent-id');
    var wallReplyInfo = document.getElementById('profile-wall-reply-info');
    var wallReplyLabel = document.getElementById('profile-wall-reply-label');
    var wallReplyCancel = document.getElementById('profile-wall-reply-cancel');
    var profileUserId = parseInt(page.getAttribute('data-profile-user-id') || '0', 10);
    var modal = document.getElementById('profile-message-modal');
    var modalTextarea = document.getElementById('profile-message-text');
    var modalForm = document.getElementById('profile-message-form');
    var blacklistButton = document.querySelector('[data-profile-toggle-blacklist]');

    function showNotice(message, isError) {
      if (!ajaxMessage) {
        return;
      }

      ajaxMessage.textContent = '';
      ajaxMessage.className = 'profile-inline-message';
      ajaxMessage.hidden = true;
    }

    function resetReplyState() {
      if (wallParentInput) {
        wallParentInput.value = '0';
      }

      if (wallReplyInfo) {
        wallReplyInfo.hidden = true;
      }

      if (wallReplyLabel) {
        wallReplyLabel.textContent = '';
      }
    }

    function replaceWall(html) {
      if (!wallContainer) {
        return;
      }

      wallContainer.innerHTML = html;
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

    function buildEditor(comment) {
      var slot = comment.querySelector('.wall-comment-editor-slot');
      var source = comment.querySelector('.wall-comment-source');
      var existing = comment.querySelector('.wall-inline-editor');
      var textarea;
      var controls;
      var saveButton;
      var cancelButton;
      var form;

      if (!slot || !source) {
        return;
      }

      if (existing) {
        existing.querySelector('textarea').focus();
        return;
      }

      form = document.createElement('form');
      form.className = 'wall-inline-editor';

      textarea = document.createElement('textarea');
      textarea.className = 'wall-inline-editor-textarea';
      textarea.value = source.value;
      form.appendChild(textarea);

      controls = document.createElement('div');
      controls.className = 'wall-inline-editor-actions';

      saveButton = document.createElement('button');
      saveButton.type = 'submit';
      saveButton.className = 'wall-form-submit wall-inline-editor-save';
      saveButton.textContent = 'Сохранить';
      controls.appendChild(saveButton);

      cancelButton = document.createElement('button');
      cancelButton.type = 'button';
      cancelButton.className = 'wall-comment-button';
      cancelButton.textContent = 'Отмена';
      cancelButton.addEventListener('click', function () {
        form.parentNode.removeChild(form);
      });
      controls.appendChild(cancelButton);

      form.appendChild(controls);
      form.addEventListener('submit', function (event) {
        var formData;

        event.preventDefault();
        formData = new FormData();
        formData.append('action', 'wall_edit');
        formData.append('object_id', String(profileUserId));
        formData.append('comment_id', String(comment.getAttribute('data-comment-id') || '0'));
        formData.append('text', textarea.value);

        sendRequest(formData, function (payload) {
          replaceWall(payload.html || '');
          showNotice(payload.message || 'Комментарий обновлен.');
        }, function (message) {
          showNotice(message, true);
        });
      });

      slot.appendChild(form);
      textarea.focus();
    }

    if (wallReplyCancel) {
      wallReplyCancel.addEventListener('click', function () {
        resetReplyState();
        if (wallTextarea) {
          wallTextarea.focus();
        }
      });
    }

    if (wallForm) {
      wallForm.addEventListener('submit', function (event) {
        var formData = new FormData(wallForm);

        event.preventDefault();
        formData.append('action', 'wall_add');

        sendRequest(formData, function (payload) {
          replaceWall(payload.html || '');
          showNotice(payload.message || 'Комментарий добавлен.');

          if (wallTextarea) {
            wallTextarea.value = '';
          }

          resetReplyState();
        }, function (message) {
          showNotice(message, true);
        });
      });
    }

    if (wallContainer) {
      wallContainer.addEventListener('click', function (event) {
        var replyButton = event.target.closest('[data-wall-reply]');
        var editButton = event.target.closest('[data-wall-edit]');
        var deleteButton = event.target.closest('[data-wall-delete]');
        var reportButton = event.target.closest('[data-wall-report]');
        var comment;
        var formData;

        if (replyButton) {
          event.preventDefault();

          if (wallParentInput) {
            wallParentInput.value = replyButton.getAttribute('data-comment-id') || '0';
          }

          if (wallReplyInfo && wallReplyLabel) {
            wallReplyLabel.textContent = 'Ответ пользователю ' + (replyButton.getAttribute('data-author-name') || '');
            wallReplyInfo.hidden = false;
          }

          if (wallTextarea) {
            wallTextarea.focus();
          }

          return;
        }

        if (editButton) {
          event.preventDefault();
          comment = editButton.closest('.wall-comment');
          if (comment) {
            buildEditor(comment);
          }
          return;
        }

        if (deleteButton) {
          event.preventDefault();

          if (!window.confirm('Удалить комментарий?')) {
            return;
          }

          comment = deleteButton.closest('.wall-comment');
          if (!comment) {
            return;
          }

          formData = new FormData();
          formData.append('action', 'wall_delete');
          formData.append('object_id', String(profileUserId));
          formData.append('comment_id', String(comment.getAttribute('data-comment-id') || '0'));

          sendRequest(formData, function (payload) {
            replaceWall(payload.html || '');
            showNotice(payload.message || 'Комментарий удален.');
            resetReplyState();
          }, function (message) {
            showNotice(message, true);
          });

          return;
        }

        if (reportButton) {
          event.preventDefault();

          comment = reportButton.closest('.wall-comment');
          if (!comment) {
            return;
          }

          if (!window.confirm('Отправить жалобу администрации?')) {
            return;
          }

          formData = new FormData();
          formData.append('action', 'wall_report');
          formData.append('object_id', String(profileUserId));
          formData.append('comment_id', String(comment.getAttribute('data-comment-id') || '0'));

          sendRequest(formData, function (payload) {
            showNotice(payload.message || 'Жалоба отправлена.');
          }, function (message) {
            showNotice(message, true);
          });
        }
      });
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
