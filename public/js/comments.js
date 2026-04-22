(function () {
  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
      return;
    }

    fn();
  }

  function getCommentTextarea(root) {
    if (!root) {
      return null;
    }

    return root.querySelector('[data-comment-textarea]') ||
      root.querySelector("textarea[name='text']") ||
      root.querySelector("textarea[name='textComment']");
  }

  function resetReplyState(root) {
    var parentInput;
    var replyBanner;
    var replyLabel;

    if (!root) {
      return;
    }

    parentInput = root.querySelector('[data-comment-parent]');
    replyBanner = root.querySelector('[data-comment-reply-banner]');
    replyLabel = root.querySelector('[data-comment-reply-label]');

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

  function activateReply(button) {
    var threadRoot = button.closest('[data-comment-thread]');
    var form = threadRoot ? threadRoot.querySelector('[data-comment-form]') : null;
    var parentInput = form ? form.querySelector('[data-comment-parent]') : null;
    var replyBanner = form ? form.querySelector('[data-comment-reply-banner]') : null;
    var replyLabel = form ? form.querySelector('[data-comment-reply-label]') : null;
    var authorName = button.getAttribute('data-author-name') || '';

    if (!form || !parentInput) {
      return;
    }

    parentInput.value = button.getAttribute('data-comment-id') || '0';

    if (replyBanner && replyLabel) {
      replyLabel.textContent = 'Ответ пользователю ' + authorName;
      replyBanner.hidden = false;
    }

    focusTextarea(form);
  }

  ready(function () {
    document.addEventListener('click', function (event) {
      var replyButton = event.target.closest('[data-comment-reply]');
      var cancelButton = event.target.closest('[data-comment-reply-cancel]');
      var form;

      if (replyButton) {
        event.preventDefault();
        activateReply(replyButton);
        return;
      }

      if (cancelButton) {
        event.preventDefault();
        form = cancelButton.closest('[data-comment-form]');
        resetReplyState(form);
        focusTextarea(form);
      }
    });

    document.addEventListener('submit', function (event) {
      var form = event.target.closest('[data-comment-form]');
      if (!form) {
        return;
      }

      if (!getCommentTextarea(form)) {
        return;
      }

      if (!getCommentTextarea(form).value.trim()) {
        resetReplyState(form);
      }
    });
  });

  window.replyWallComment = function (userName) {
    var activeForm = document.querySelector('[data-comment-form]');
    var textarea = getCommentTextarea(activeForm);
    var cleanUserName = String(userName || '').replace(/\s+/g, ' ').trim();
    var prefix;

    if (!textarea || !cleanUserName) {
      return false;
    }

    prefix = '[b]' + cleanUserName + '[/b], ';
    if (textarea.value.indexOf(prefix) !== 0) {
      textarea.value = prefix + textarea.value;
    }

    focusTextarea(activeForm);
    return false;
  };
})();
