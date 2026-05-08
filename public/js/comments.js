(function () {
  var AJAX_URL = '/ajax/comments.php';

  // WeakMap to track auto-hide timers per notice element
  var noticeTimers = typeof WeakMap === 'function' ? new WeakMap() : null;

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
      return;
    }

    fn();
  }

  // ─── XHR helper ────────────────────────────────────────────────
  function sendAjax(formData, onSuccess, onError) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', AJAX_URL, true);
    xhr.onreadystatechange = function () {
      var payload;

      if (xhr.readyState !== 4) {
        return;
      }

      try {
        payload = JSON.parse(xhr.responseText || '{}');
      } catch (e) {
        if (typeof onError === 'function') {
          onError('Не удалось обработать ответ сервера. Попробуйте обновить страницу.');
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

  // ─── Thread root helpers ────────────────────────────────────────
  function getThreadRoot(el) {
    return el ? el.closest('[data-comment-thread]') : null;
  }

  function getThreadData(threadRoot) {
    if (!threadRoot) {
      return {};
    }

    return {
      type:     threadRoot.getAttribute('data-comment-type') || '',
      objectId: threadRoot.getAttribute('data-object-id') || '0',
      file:     threadRoot.getAttribute('data-file') || '',
    };
  }

  // ─── Inline notice ─────────────────────────────────────────────
  function showThreadNotice(threadRoot, message, isError) {
    var notice;

    if (!threadRoot) {
      return;
    }

    notice = threadRoot.querySelector('[data-comment-notice]');

    if (!notice) {
      return;
    }

    notice.textContent = message || '';
    notice.className = 'comment-ajax-notice' + (isError ? ' comment-ajax-notice-error' : ' comment-ajax-notice-success');
    notice.hidden = !message;

    if (message) {
      if (noticeTimers) {
        clearTimeout(noticeTimers.get(notice));
        noticeTimers.set(notice, setTimeout(function () {
          notice.hidden = true;
        }, 5000));
      } else {
        clearTimeout(notice._hideTimer);
        notice._hideTimer = setTimeout(function () {
          notice.hidden = true;
        }, 5000);
      }
    }
  }

  // ─── Comment stream refresh ────────────────────────────────────
  function refreshStream(threadRoot, streamHtml, highlightId) {
    var stream, tmp, newStream;

    if (!threadRoot || !streamHtml) {
      return;
    }

    stream = threadRoot.querySelector('[data-comment-stream]');

    if (!stream) {
      return;
    }

    tmp = document.createElement('div');
    tmp.innerHTML = streamHtml;
    newStream = tmp.querySelector('[data-comment-stream]');

    if (newStream) {
      stream.innerHTML = newStream.innerHTML;
    } else {
      stream.innerHTML = streamHtml;
    }

    if (highlightId) {
      setTimeout(function () {
        var el = threadRoot.querySelector('[data-comment-id="' + highlightId + '"]');

        if (el) {
          el.classList.add('comment-entry-highlight');
          setTimeout(function () {
            el.classList.remove('comment-entry-highlight');
          }, 2000);
          el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }, 30);
    }
  }

  // ─── Reply state ───────────────────────────────────────────────
  function resetReplyState(root) {
    var parentInput, replyBanner, replyLabel;

    if (!root) {
      return;
    }

    parentInput = root.querySelector('[data-comment-parent]');
    replyBanner = root.querySelector('[data-comment-reply-banner]');
    replyLabel  = root.querySelector('[data-comment-reply-label]');

    if (parentInput) {
      parentInput.value = '0';
    }

    if (replyLabel) {
      replyLabel.textContent = '';
    }

    if (replyBanner) {
      replyBanner.hidden = true;
    }
  }

  function getCommentTextarea(root) {
    if (!root) {
      return null;
    }

    return root.querySelector('[data-comment-textarea]') ||
      root.querySelector("textarea[name='text']") ||
      root.querySelector("textarea[name='textComment']");
  }

  function focusTextarea(root) {
    var textarea = getCommentTextarea(root);

    if (!textarea) {
      return;
    }

    textarea.focus();

    if (typeof textarea.setSelectionRange === 'function') {
      textarea.setSelectionRange(textarea.value.length, textarea.value.length);
    }
  }

  // ─── Reply button ──────────────────────────────────────────────
  function activateReply(button) {
    var threadRoot = getThreadRoot(button);
    var form       = threadRoot ? threadRoot.querySelector('[data-comment-form]') : null;
    var parentInput, replyBanner, replyLabel, authorName;

    if (!form) {
      return;
    }

    parentInput = form.querySelector('[data-comment-parent]');
    replyBanner = form.querySelector('[data-comment-reply-banner]');
    replyLabel  = form.querySelector('[data-comment-reply-label]');
    authorName  = button.getAttribute('data-author-name') || '';

    if (!parentInput) {
      return;
    }

    parentInput.value = button.getAttribute('data-comment-id') || '0';

    if (replyBanner && replyLabel) {
      replyLabel.textContent = 'Ответ пользователю ' + authorName;
      replyBanner.hidden = false;
    }

    focusTextarea(form);
  }

  // ─── Inline editor ─────────────────────────────────────────────
  function buildInlineEditor(commentEl, threadRoot) {
    var slot     = commentEl.querySelector('.wall-comment-editor-slot');
    var sourceEl = commentEl.querySelector('.wall-comment-source');
    var existing = commentEl.querySelector('.wall-inline-editor');
    var editLink = commentEl.querySelector('[data-wall-edit]');
    var td       = getThreadData(threadRoot);
    var commentId = commentEl.getAttribute('data-comment-id') || '0';
    var csrfToken = editLink ? (editLink.getAttribute('data-csrf-token') || '') : '';
    var form, textarea, controls, saveBtn, cancelBtn;

    if (!slot || !sourceEl) {
      return;
    }

    if (existing) {
      var et = existing.querySelector('textarea');

      if (et) {
        et.focus();
      }

      return;
    }

    form = document.createElement('form');
    form.className = 'wall-inline-editor';

    textarea = document.createElement('textarea');
    textarea.className = 'wall-inline-editor-textarea';
    textarea.value = sourceEl.value;
    form.appendChild(textarea);

    controls = document.createElement('div');
    controls.className = 'wall-inline-editor-actions';

    saveBtn = document.createElement('button');
    saveBtn.type = 'submit';
    saveBtn.className = 'wall-form-submit wall-inline-editor-save';
    saveBtn.textContent = 'Сохранить';
    controls.appendChild(saveBtn);

    cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'wall-comment-button';
    cancelBtn.textContent = 'Отмена';
    cancelBtn.addEventListener('click', function () {
      if (form.parentNode) {
        form.parentNode.removeChild(form);
      }
    });
    controls.appendChild(cancelBtn);

    form.appendChild(controls);

    form.addEventListener('submit', function (event) {
      var formData;

      event.preventDefault();

      if (!textarea.value.trim()) {
        showThreadNotice(threadRoot, 'Введите текст комментария.', true);
        return;
      }

      saveBtn.disabled = true;
      saveBtn.textContent = 'Сохранение…';

      formData = new FormData();
      formData.append('action', 'edit');
      formData.append('type', td.type);
      formData.append('object_id', td.objectId);
      formData.append('comment_id', commentId);
      formData.append('text', textarea.value);
      formData.append('file', td.file);

      if (csrfToken) {
        formData.append('csrf_token', csrfToken);
      }

      sendAjax(formData, function (payload) {
        refreshStream(threadRoot, payload.html || '', payload.comment_id || commentId);
        showThreadNotice(threadRoot, payload.message || 'Комментарий обновлён.');
      }, function (message) {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Сохранить';
        showThreadNotice(threadRoot, message, true);
      });
    });

    slot.appendChild(form);
    textarea.focus();
  }

  // ─── Main event delegation ─────────────────────────────────────
  ready(function () {

    document.addEventListener('click', function (event) {
      var target      = event.target;
      var replyBtn    = target.closest('[data-comment-reply]');
      var cancelBtn   = target.closest('[data-comment-reply-cancel]');
      var editBtn     = target.closest('[data-wall-edit]');
      var deleteBtn   = target.closest('[data-wall-delete]');
      var reportBtn   = target.closest('[data-wall-report]');
      var threadRoot, comment, commentId, csrfToken, td, formData;

      // ── Reply ────────────────────────────────────────────────
      if (replyBtn) {
        event.preventDefault();
        activateReply(replyBtn);
        return;
      }

      // ── Cancel reply ─────────────────────────────────────────
      if (cancelBtn) {
        event.preventDefault();
        var replyForm = cancelBtn.closest('[data-comment-form]');
        resetReplyState(replyForm);
        focusTextarea(replyForm);
        return;
      }

      // ── Edit (only inside a data-comment-thread) ─────────────
      if (editBtn) {
        threadRoot = getThreadRoot(editBtn);

        if (!threadRoot) {
          return; // let profile.js handle its own wall
        }

        event.preventDefault();
        comment = editBtn.closest('.wall-comment');

        if (comment) {
          buildInlineEditor(comment, threadRoot);
        }

        return;
      }

      // ── Delete ───────────────────────────────────────────────
      if (deleteBtn) {
        threadRoot = getThreadRoot(deleteBtn);

        if (!threadRoot) {
          return;
        }

        event.preventDefault();

        if (!window.confirm('Удалить комментарий?')) {
          return;
        }

        comment   = deleteBtn.closest('.wall-comment');

        if (!comment) {
          return;
        }

        commentId = comment.getAttribute('data-comment-id') || '0';
        csrfToken = deleteBtn.getAttribute('data-csrf-token') || '';
        td        = getThreadData(threadRoot);

        formData  = new FormData();
        formData.append('action', 'delete');
        formData.append('type', td.type);
        formData.append('object_id', td.objectId);
        formData.append('comment_id', commentId);
        formData.append('file', td.file);

        if (csrfToken) {
          formData.append('csrf_token', csrfToken);
        }

        sendAjax(formData, function (payload) {
          refreshStream(threadRoot, payload.html || '');
          showThreadNotice(threadRoot, payload.message || 'Комментарий удалён.');
          resetReplyState(threadRoot.querySelector('[data-comment-form]'));
        }, function (message) {
          showThreadNotice(threadRoot, message, true);
        });

        return;
      }

      // ── Report ───────────────────────────────────────────────
      if (reportBtn) {
        threadRoot = getThreadRoot(reportBtn);

        if (!threadRoot) {
          return;
        }

        event.preventDefault();

        if (!window.confirm('Отправить жалобу администрации?')) {
          return;
        }

        comment   = reportBtn.closest('.wall-comment');

        if (!comment) {
          return;
        }

        commentId = comment.getAttribute('data-comment-id') || '0';
        csrfToken = reportBtn.getAttribute('data-csrf-token') || '';
        td        = getThreadData(threadRoot);

        formData  = new FormData();
        formData.append('action', 'report');
        formData.append('type', td.type);
        formData.append('object_id', td.objectId);
        formData.append('comment_id', commentId);
        formData.append('file', td.file);

        if (csrfToken) {
          formData.append('csrf_token', csrfToken);
        }

        sendAjax(formData, function (payload) {
          showThreadNotice(threadRoot, payload.message || 'Жалоба отправлена.');
        }, function (message) {
          showThreadNotice(threadRoot, message, true);
        });

        return;
      }

    });

    // ── Form submit (add comment) ───────────────────────────────
    document.addEventListener('submit', function (event) {
      var form = event.target.closest('[data-comment-form]');
      var threadRoot, textarea, submitBtn, formData, td;

      if (!form) {
        return;
      }

      threadRoot = getThreadRoot(form);

      if (!threadRoot) {
        return; // profile wall — handled by profile.js
      }

      textarea = getCommentTextarea(form);

      if (textarea && !textarea.value.trim()) {
        resetReplyState(form);
        return; // let native validation fire
      }

      event.preventDefault();

      td        = getThreadData(threadRoot);
      submitBtn = form.querySelector('[type="submit"]');

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.value    = 'Отправка…';
      }

      formData = new FormData(form);
      formData.append('action', 'add'); // AJAX action flag

      sendAjax(formData, function (payload) {
        var newId = payload.comment_id || 0;

        refreshStream(threadRoot, payload.html || '', newId);

        if (newId) {
          setTimeout(function () {
            var newEl = threadRoot.querySelector('[data-comment-id="' + newId + '"]');

            if (newEl) {
              newEl.classList.add('comment-entry-new');
            }
          }, 60);
        }

        if (textarea) {
          textarea.value = '';
        }

        resetReplyState(form);
        showThreadNotice(threadRoot, payload.message || 'Комментарий добавлен.');

        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.value    = 'Отправить';
        }
      }, function (message) {
        showThreadNotice(threadRoot, message, true);

        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.value    = 'Отправить';
        }
      });
    });
  });

  // Legacy global helper (kept for backward compatibility)
  window.replyWallComment = function (userName) {
    var activeForm = document.querySelector('[data-comment-form]');
    var textarea   = getCommentTextarea(activeForm || document);

    if (activeForm) {
      resetReplyState(activeForm);
    }

    if (!textarea) {
      return false;
    }

    textarea.focus();

    if (typeof textarea.setSelectionRange === 'function') {
      textarea.setSelectionRange(textarea.value.length, textarea.value.length);
    }

    return false;
  };
})();
