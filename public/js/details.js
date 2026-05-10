(function () {
  function onReady(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
      return;
    }

    callback();
  }

  function initBookmarkButton() {
    var button = document.querySelector('[data-details-bookmark]');
    if (!button || typeof window.fetch !== 'function') {
      return;
    }

    button.addEventListener('click', function (event) {
      var href = button.getAttribute('href') || '';

      if (!href || button.classList.contains('is-loading')) {
        return;
      }

      event.preventDefault();
      button.classList.add('is-loading');

      var requestUrl = href + (href.indexOf('?') === -1 ? '?' : '&') + 'ajax=1';

      window.fetch(requestUrl, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Request failed');
          }

          return response.json();
        })
        .then(function (payload) {
          if (!payload || !payload.success) {
            throw new Error((payload && payload.message) || 'Request failed');
          }

          if (payload.href) {
            button.setAttribute('href', payload.href);
          }

          if (payload.label) {
            button.textContent = payload.label;
          }

          if (payload.bookmarked) {
            button.classList.add('details-bookmark-button-active');
            button.setAttribute('data-bookmarked', '1');
          } else {
            button.classList.remove('details-bookmark-button-active');
            button.setAttribute('data-bookmarked', '0');
          }
        })
        .catch(function () {
          window.location.href = href;
        })
        .finally(function () {
          button.classList.remove('is-loading');
        });
    });
  }

  function initTrackerRefreshButton() {
    var button = document.querySelector('[data-details-trackers-refresh]');
    if (!button || typeof window.fetch !== 'function') {
      return;
    }

    button.addEventListener('click', function (event) {
      var href = button.getAttribute('href') || '';

      if (!href || button.classList.contains('is-loading')) {
        return;
      }

      event.preventDefault();
      button.classList.add('is-loading');
      button.textContent = 'Проверяю...';

      var requestUrl = href + (href.indexOf('?') === -1 ? '?' : '&') + 'ajax=1';

      window.fetch(requestUrl, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Request failed');
          }

          return response.json();
        })
        .then(function (payload) {
          if (!payload || !payload.success) {
            throw new Error((payload && payload.message) || 'Request failed');
          }

          window.location.reload();
        })
        .catch(function () {
          window.location.href = href;
        });
    });
  }

  function initRatingControl() {
    var root = document.querySelector('[data-details-rating]');
    if (!root || typeof window.fetch !== 'function') {
      return;
    }

    var isLoading = false;
    var endpoint = root.getAttribute('data-rating-endpoint') || 'api/ratings.php';
    var torrentId = root.getAttribute('data-torrent-id') || '';
    var csrf = root.getAttribute('data-rating-csrf') || '';
    var starsBox = root.querySelector('[data-details-rating-stars]');
    var fill = root.querySelector('[data-details-rating-fill]');
    var count = root.querySelector('[data-details-rating-count]');
    var message = root.querySelector('[data-details-rating-message]');
    var control = root.querySelector('[data-details-rating-control]');

    function showMessage(text, isError) {
      if (!message) {
        return;
      }

      message.textContent = text || '';
      message.hidden = !text;
      message.classList.toggle('details-rating-note-error', !!isError);
    }

    function setSelectedRating(userRating) {
      var stars = root.querySelectorAll('[data-details-rating-star]');

      if (control) {
        control.classList.remove('details-rating-vote');
        control.classList.add('details-rating-voted');
        control.setAttribute('aria-label', 'Вы оценили на ' + userRating + ' из 5');
      }

      Array.prototype.forEach.call(stars, function (star) {
        var value = parseInt(star.getAttribute('data-rating-value') || '0', 10);

        star.classList.remove('details-rating-vote-star');
        star.classList.add('details-rating-voted-star');
        star.classList.toggle('details-rating-voted-star-selected', value === userRating);

        if (!star.getAttribute('role')) {
          star.setAttribute('role', 'button');
        }
        if (!star.getAttribute('tabindex')) {
          star.setAttribute('tabindex', '0');
        }
      });
    }

    function applyPayload(payload) {
      var ratingAvg = parseFloat(payload.rating_avg || 0);
      var ratingCount = parseInt(payload.rating_count || 0, 10);
      var userRating = parseInt(payload.user_rating || 0, 10);
      var percent = Math.max(0, Math.min(100, (ratingAvg / 5) * 100));

      if (fill) {
        fill.style.width = percent + '%';
      }

      if (starsBox) {
        starsBox.setAttribute('aria-label', 'Рейтинг ' + ratingAvg.toFixed(1));
      }

      if (count) {
        count.textContent = '(' + ratingCount.toLocaleString('ru-RU') + ' оценок)';
      }

      root.setAttribute('data-user-rating', String(userRating));
      if (userRating > 0) {
        setSelectedRating(userRating);
      }

      showMessage(payload.message || 'Рейтинг сохранён.', false);
    }

    function sendRating(value) {
      var body;

      if (isLoading) {
        return;
      }

      if (!torrentId || !csrf || value < 1 || value > 5) {
        showMessage('Не удалось отправить оценку.', true);
        return;
      }

      isLoading = true;
      root.setAttribute('data-rating-loading', '1');
      body = new URLSearchParams();
      body.set('torrent_id', torrentId);
      body.set('rating', String(value));
      body.set('csrf_token', csrf);

      window.fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-CSRF-Token': csrf
        },
        body: body.toString()
      })
        .then(function (response) {
          return response.json().catch(function () {
            throw new Error('Не удалось обработать ответ сервера.');
          });
        })
        .then(function (payload) {
          if (!payload || !payload.ok) {
            throw new Error((payload && payload.message) ? payload.message : 'Не удалось сохранить рейтинг.');
          }

          applyPayload(payload);
        })
        .catch(function (error) {
          showMessage(error && error.message ? error.message : 'Не удалось сохранить рейтинг.', true);
        })
        .finally(function () {
          isLoading = false;
          root.removeAttribute('data-rating-loading');
        });
    }

    root.addEventListener('click', function (event) {
      var star = event.target.closest('[data-details-rating-star]');
      var value;

      if (!star || !root.contains(star)) {
        return;
      }

      event.preventDefault();
      value = parseInt(star.getAttribute('data-rating-value') || '0', 10);
      sendRating(value);
    });

    root.addEventListener('keydown', function (event) {
      var star = event.target.closest('[data-details-rating-star]');
      var value;

      if (!star || !root.contains(star) || (event.key !== 'Enter' && event.key !== ' ')) {
        return;
      }

      event.preventDefault();
      value = parseInt(star.getAttribute('data-rating-value') || '0', 10);
      sendRating(value);
    });
  }

  function initScreenshotZoom() {
    var items = document.querySelectorAll('[data-details-screenshot-zoom]');

    if (!items.length) {
      return;
    }

    Array.prototype.forEach.call(items, function (item) {
      var image = item.querySelector('img');
      var zoomSrc = item.getAttribute('data-zoom-src') || (image ? image.getAttribute('src') : '');
      var zoomWindow = document.createElement('div');

      if (!image || !zoomSrc) {
        return;
      }

      zoomWindow.className = 'details-gallery-zoom-window';
      zoomWindow.style.backgroundImage = 'url("' + zoomSrc.replace(/"/g, '%22') + '")';
      item.appendChild(zoomWindow);

      function refreshZoomSize() {
        var rect = image.getBoundingClientRect();

        zoomWindow.style.width = Math.round(rect.width) + 'px';
        zoomWindow.style.height = Math.round(rect.height) + 'px';
      }

      function positionZoom(event) {
        var rect = image.getBoundingClientRect();
        var naturalWidth = image.naturalWidth || rect.width;
        var naturalHeight = image.naturalHeight || rect.height;
        var x = Math.max(0, Math.min(rect.width, event.clientX - rect.left));
        var y = Math.max(0, Math.min(rect.height, event.clientY - rect.top));
        var xPercent = rect.width ? (x / rect.width) * 100 : 50;
        var yPercent = rect.height ? (y / rect.height) * 100 : 50;

        zoomWindow.style.backgroundSize = naturalWidth + 'px ' + naturalHeight + 'px';
        zoomWindow.style.backgroundPosition = xPercent + '% ' + yPercent + '%';
      }

      item.addEventListener('mouseenter', function (event) {
        refreshZoomSize();
        positionZoom(event);
        item.classList.add('is-zooming');
      });

      item.addEventListener('mousemove', positionZoom);

      item.addEventListener('mouseleave', function () {
        item.classList.remove('is-zooming');
      });

      if (image.complete) {
        refreshZoomSize();
      } else {
        image.addEventListener('load', refreshZoomSize);
      }
    });
  }

  function initPostTts() {
    var button = document.querySelector('[data-details-tts]');
    var source = document.querySelector('[data-details-tts-text]');

    if (!button || !source || !('speechSynthesis' in window) || typeof window.SpeechSynthesisUtterance !== 'function') {
      if (button) {
        button.hidden = true;
      }
      return;
    }

    button.addEventListener('click', function () {
      var text = (source.textContent || '').trim();
      var utterance;

      if (!text) {
        return;
      }

      if (window.speechSynthesis.speaking) {
        window.speechSynthesis.cancel();
        button.textContent = 'Озвучить пост';
        return;
      }

      utterance = new window.SpeechSynthesisUtterance(text);
      utterance.lang = 'ru-RU';
      utterance.rate = 1;
      utterance.onend = function () {
        button.textContent = 'Озвучить пост';
      };
      utterance.onerror = function () {
        button.textContent = 'Озвучить пост';
      };

      button.textContent = 'Остановить';
      window.speechSynthesis.speak(utterance);
    });
  }

  onReady(function () {
    initScreenshotZoom();
    initBookmarkButton();
    initTrackerRefreshButton();
    initRatingControl();
    initPostTts();
  });
})();
