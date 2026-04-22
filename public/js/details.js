(function () {
  function onReady(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
      return;
    }

    callback();
  }

  function initLightbox() {
    if (typeof window.GLightbox !== 'function') {
      return;
    }

    window.GLightbox({
      selector: '#details-gallery .details-gallery-item',
      loop: true,
      touchNavigation: true,
      draggable: true,
      zoomable: true,
      openEffect: 'zoom',
      closeEffect: 'fade',
      slideEffect: 'slide',
      descPosition: 'bottom'
    });
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

  onReady(function () {
    initLightbox();
    initBookmarkButton();
  });
})();
