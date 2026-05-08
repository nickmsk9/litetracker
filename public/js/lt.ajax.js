(function (window, document) {
  'use strict';

  var LiteTracker = window.LiteTracker = window.LiteTracker || {};

  function isPlainObject(value) {
    return Object.prototype.toString.call(value) === '[object Object]';
  }

  function appendParams(url, params) {
    var query;
    var separator;

    if (!params) {
      return url;
    }

    query = new URLSearchParams();

    Object.keys(params).forEach(function (key) {
      var value = params[key];

      if (value === undefined || value === null) {
        return;
      }

      if (Array.isArray(value)) {
        value.forEach(function (item) {
          if (item !== undefined && item !== null) {
            query.append(key, String(item));
          }
        });
        return;
      }

      query.append(key, String(value));
    });

    query = query.toString();
    if (!query) {
      return url;
    }

    separator = url.indexOf('?') === -1 ? '?' : '&';
    return url + separator + query;
  }

  function normalizeBody(data, headers) {
    if (!data) {
      return data;
    }

    if (data instanceof FormData || data instanceof URLSearchParams || data instanceof Blob) {
      return data;
    }

    if (isPlainObject(data)) {
      headers['Content-Type'] = headers['Content-Type'] || 'application/json';
      return JSON.stringify(data);
    }

    return data;
  }

  LiteTracker.ajax = LiteTracker.ajax || {};

  LiteTracker.ajax.request = function (url, options) {
    var requestOptions = options || {};
    var headers = {};
    var fetchOptions;

    Object.keys(requestOptions.headers || {}).forEach(function (key) {
      headers[key] = requestOptions.headers[key];
    });

    headers.Accept = headers.Accept || 'application/json';
    headers['X-Requested-With'] = headers['X-Requested-With'] || 'XMLHttpRequest';

    fetchOptions = {
      method: requestOptions.method || 'GET',
      credentials: requestOptions.credentials || 'same-origin',
      headers: headers
    };

    if (requestOptions.body !== undefined) {
      fetchOptions.body = normalizeBody(requestOptions.body, headers);
    }

    return window.fetch(url, fetchOptions)
      .then(function (response) {
        return response.text().then(function (text) {
          var payload = {};

          if (text) {
            try {
              payload = JSON.parse(text);
            } catch (error) {
              payload = {
                ok: false,
                message: 'Не удалось обработать ответ сервера.',
                data: null,
                html: text,
                errors: [error.message],
                reload: false
              };
            }
          }

          payload.ok = !!payload.ok;
          payload.message = payload.message || '';
          payload.data = payload.data || {};
          payload.html = payload.html || '';
          payload.errors = payload.errors || [];
          payload.reload = !!payload.reload;
          payload.status = response.status;
          payload.response = response;

          if (!response.ok && !payload.message) {
            payload.message = 'Произошла ошибка.';
          }

          return payload;
        });
      });
  };

  LiteTracker.ajax.post = function (url, data, options) {
    var requestOptions = options || {};
    requestOptions.method = requestOptions.method || 'POST';
    requestOptions.body = data;
    return LiteTracker.ajax.request(url, requestOptions);
  };

  LiteTracker.ajax.get = function (url, params, options) {
    var requestOptions = options || {};
    requestOptions.method = requestOptions.method || 'GET';
    return LiteTracker.ajax.request(appendParams(url, params), requestOptions);
  };

  LiteTracker.ui = LiteTracker.ui || {};

  LiteTracker.ui.showNotice = function (container, message, type) {
    if (!container) {
      return;
    }

    container.textContent = message || '';
    container.hidden = !message;

    if (type) {
      container.setAttribute('data-notice-type', type);
    } else {
      container.removeAttribute('data-notice-type');
    }
  };

  LiteTracker.ui.setButtonLoading = function (button, loading, text) {
    if (!button) {
      return;
    }

    if (!button.getAttribute('data-lt-original-text')) {
      button.setAttribute('data-lt-original-text', button.value || button.textContent || '');
    }

    button.disabled = !!loading;

    if (loading && text) {
      if ('value' in button) {
        button.value = text;
      } else {
        button.textContent = text;
      }
      return;
    }

    if (!loading) {
      if ('value' in button) {
        button.value = button.getAttribute('data-lt-original-text') || '';
      } else {
        button.textContent = button.getAttribute('data-lt-original-text') || '';
      }
    }
  };

  LiteTracker.ui.confirm = function (message) {
    return window.confirm(message);
  };
})(window, document);
