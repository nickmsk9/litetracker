(function () {
  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
      return;
    }
    callback();
  }

  function jsonFetch(url, options) {
    options = options || {};
    options.credentials = 'same-origin';
    options.headers = options.headers || {};
    options.headers.Accept = 'application/json';
    options.headers['X-Requested-With'] = 'XMLHttpRequest';

    return window.fetch(url, options).then(function (response) {
      return response.json().then(function (payload) {
        if (!response.ok || !payload || !payload.ok) {
          throw new Error((payload && payload.message) || 'Request failed');
        }
        return payload;
      });
    });
  }

  function setBadge(root, count) {
    var badge = root.querySelector('[data-notifications-badge]');
    var button = root.querySelector('[data-notifications-toggle]');
    count = Math.max(0, Number(count) || 0);

    if (!badge) {
      return;
    }

    badge.textContent = count > 99 ? '99+' : String(count);
    badge.hidden = count <= 0;

    if (button) {
      button.classList.toggle('site-alert-button-active', count > 0);
      button.setAttribute('aria-label', count > 0 ? 'Уведомления: ' + badge.textContent : 'Уведомления');
    }
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[char];
    });
  }

  function renderItems(root, items) {
    var list = root.querySelector('[data-notifications-list]');
    var empty = root.querySelector('[data-notifications-empty]');

    if (!list) {
      return;
    }

    if (!items || !items.length) {
      list.innerHTML = '';
      if (empty) {
        empty.hidden = false;
      }
      return;
    }

    if (empty) {
      empty.hidden = true;
    }

    list.innerHTML = items.map(function (item) {
      var id = Number(item.id) || 0;
      var url = item.url || 'notifications.php';
      var avatar = item.actor && item.actor.avatar ? item.actor.avatar : 'public/images/default_avatar.gif';
      var unreadClass = item.is_read ? '' : ' site-notification-item-unread';
      var message = item.message ? '<span>' + escapeHtml(item.message) + '</span>' : '';
      var archive = '<button type="button" data-notification-archive="' + id + '" title="В архив">×</button>';

      return '' +
        '<article class="site-notification-item' + unreadClass + '" data-notification-id="' + id + '">' +
          '<a href="' + escapeHtml(url) + '">' +
            '<img src="' + escapeHtml(avatar) + '" alt="" width="30" height="30">' +
            '<span class="site-notification-copy">' +
              '<strong>' + escapeHtml(item.title) + '</strong>' +
              message +
              '<time>' + escapeHtml(item.created_label || '') + '</time>' +
            '</span>' +
          '</a>' +
          archive +
        '</article>';
    }).join('');
  }

  function formBody(root, ids) {
    var data = new URLSearchParams();
    var csrf = root.getAttribute('data-notifications-csrf') || '';
    data.append('csrf_token', csrf);
    (ids || []).forEach(function (id) {
      data.append('ids[]', String(id));
    });
    return data;
  }

  function loadCount(root) {
    var url = root.getAttribute('data-notifications-count-url') || '';
    if (!url || typeof window.fetch !== 'function') {
      return Promise.resolve();
    }

    return jsonFetch(url).then(function (payload) {
      setBadge(root, payload.unread_count);
    }).catch(function () {});
  }

  function loadList(root) {
    var url = root.getAttribute('data-notifications-list-url') || '';
    if (!url || typeof window.fetch !== 'function') {
      return Promise.resolve();
    }

    return jsonFetch(url).then(function (payload) {
      if (payload.csrf_token) {
        root.setAttribute('data-notifications-csrf', payload.csrf_token);
      }
      setBadge(root, payload.unread_count);
      renderItems(root, payload.items || []);
    }).catch(function () {
      var empty = root.querySelector('[data-notifications-empty]');
      if (empty) {
        empty.hidden = false;
        empty.textContent = 'Не удалось загрузить уведомления.';
      }
    });
  }

  function postAction(root, endpoint, ids) {
    if (!endpoint || typeof window.fetch !== 'function') {
      window.location.href = 'notifications.php';
      return Promise.resolve();
    }

    return jsonFetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: formBody(root, ids)
    }).then(function (payload) {
      setBadge(root, payload.unread_count);
      return loadList(root);
    });
  }

  function initRoot(root) {
    var toggle = root.querySelector('[data-notifications-toggle]');
    var dropdown = root.querySelector('[data-notifications-dropdown]');
    var markAll = root.querySelector('[data-notifications-mark-all]');

    loadCount(root);
    window.setInterval(function () {
      loadCount(root);
    }, 60000);

    if (toggle && dropdown) {
      toggle.addEventListener('click', function (event) {
        event.preventDefault();
        dropdown.hidden = !dropdown.hidden;
        if (!dropdown.hidden) {
          loadList(root);
        }
      });

      document.addEventListener('click', function (event) {
        if (!root.contains(event.target)) {
          dropdown.hidden = true;
        }
      });
    }

    if (markAll) {
      markAll.addEventListener('click', function () {
        postAction(root, root.getAttribute('data-notifications-mark-all-url') || '', []);
      });
    }

    root.addEventListener('click', function (event) {
      var archiveButton = event.target.closest ? event.target.closest('[data-notification-archive]') : null;
      if (!archiveButton) {
        return;
      }
      event.preventDefault();
      postAction(root, root.getAttribute('data-notifications-archive-url') || '', [archiveButton.getAttribute('data-notification-archive')]);
    });
  }

  ready(function () {
    if (typeof window.fetch !== 'function' || typeof URLSearchParams === 'undefined') {
      return;
    }

    var roots = document.querySelectorAll('[data-notifications-root]');
    Array.prototype.forEach.call(roots, initRoot);
  });
}());
