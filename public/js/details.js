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

  onReady(function () {
    initScreenshotZoom();
    initBookmarkButton();
  });
})();
