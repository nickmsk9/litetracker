(function () {
    function debounce(fn, wait) {
        var timer = 0;
        return function () {
            var ctx = this;
            var args = arguments;
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                fn.apply(ctx, args);
            }, wait);
        };
    }

    function parseHtml(html) {
        var parser = new DOMParser();
        return parser.parseFromString(html, "text/html");
    }

    function sameBrowseLink(href) {
        try {
            var url = new URL(href, window.location.href);
            return url.pathname.split("/").pop() === "browse.php";
        } catch (error) {
            return false;
        }
    }

    function readViewExplicit() {
        var params = new URLSearchParams(window.location.search || "");
        return params.has("view");
    }

    function renderSuggestions(box, payload) {
        var html = "";

        function renderItems(title, items, kind) {
            var out = "";
            if (!items || !items.length) {
                return out;
            }

            out += '<div class="browse-suggest-group">';
            out += '<div class="browse-suggest-title">' + title + "</div>";
            out += '<div class="browse-suggest-items">';

            for (var i = 0; i < items.length; i++) {
                if (kind === "category") {
                    out +=
                        '<button type="button" class="browse-suggest-item" data-browse-suggest-category="' +
                        String(items[i].id) +
                        '">' +
                        String(items[i].name) +
                        "</button>";
                } else {
                    out +=
                        '<button type="button" class="browse-suggest-item" data-browse-suggest-term="' +
                        String(items[i]).replace(/"/g, "&quot;") +
                        '">' +
                        String(items[i]) +
                        "</button>";
                }
            }

            out += "</div></div>";
            return out;
        }

        html += renderItems("Последние поиски", payload.recent || [], "term");
        html += renderItems("Популярные запросы", payload.popular || [], "term");
        html += renderItems("Быстрые теги", payload.tags || [], "term");
        html += renderItems("Категории", payload.categories || [], "category");

        if (!html) {
            box.hidden = true;
            box.innerHTML = "";
            return;
        }

        box.innerHTML = html;
        box.hidden = false;
    }

    function setupBrowsePage(root) {
        if (!root) {
            return;
        }

        var page = root;
        var searchForm = page.querySelector("[data-browse-search-form]");
        var filterForm = page.querySelector("[data-browse-filter-form]");
        var searchInput = page.querySelector("[data-browse-search-input]");
        var suggestBox = page.querySelector("[data-browse-suggest]");
        var viewInputs = page.querySelectorAll("[data-browse-view-input]");
        var viewButtons = page.querySelectorAll("[data-browse-view-toggle]");
        var list = page.querySelector("[data-browse-list]");
        var storageKey = "litetrackerBrowseView";
        var currentFetch = 0;

        function syncFilterMoreLabels() {
            var filterMoreToggles = page.querySelectorAll(".browse-filter-more-toggle");
            for (var k = 0; k < filterMoreToggles.length; k++) {
                (function (toggle) {
                    var details = toggle.parentNode;
                    if (!details) {
                        return;
                    }

                    function syncToggleLabel() {
                        toggle.textContent = details.open
                            ? toggle.getAttribute("data-open-label") || "Скрыть"
                            : toggle.getAttribute("data-closed-label") || "";
                    }

                    details.addEventListener("toggle", syncToggleLabel);
                    syncToggleLabel();
                })(filterMoreToggles[k]);
            }
        }

        function setLoading(loading) {
            if (!page) {
                return;
            }
            page.classList.toggle("is-loading", !!loading);
        }

        function setView(view, syncUrl) {
            if (!list) {
                return;
            }

            list.setAttribute("data-view", view);

            for (var i = 0; i < viewInputs.length; i++) {
                viewInputs[i].value = view;
            }

            for (var j = 0; j < viewButtons.length; j++) {
                var active = viewButtons[j].getAttribute("data-browse-view") === view;
                viewButtons[j].classList.toggle("is-active", active);
                viewButtons[j].setAttribute("aria-pressed", active ? "true" : "false");
            }

            try {
                window.localStorage.setItem(storageKey, view);
            } catch (error) {}

            if (syncUrl && window.history && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.set("view", view);
                window.history.replaceState({}, "", url.toString());
            }
        }

        function buildUrlFromForm(form) {
            var url = new URL(form.getAttribute("action") || "browse.php", window.location.href);
            var data = new FormData(form);
            url.search = "";
            data.forEach(function (value, key) {
                if (value !== "") {
                    url.searchParams.append(key, value);
                }
            });
            return url;
        }

        function updateRecentSearches(term) {
            if (!term) {
                return;
            }

            try {
                var key = "litetrackerBrowseRecentSearches";
                var current = JSON.parse(window.localStorage.getItem(key) || "[]");
                var next = [term]
                    .concat(
                        current.filter(function (item) {
                            return item !== term;
                        }),
                    )
                    .slice(0, 8);
                window.localStorage.setItem(key, JSON.stringify(next));
            } catch (error) {}
        }

        function fetchAndReplace(url, pushHistory) {
            currentFetch += 1;
            var requestId = currentFetch;
            var keepSearchFocus = searchInput && document.activeElement === searchInput;

            setLoading(true);

            return fetch(url.toString(), {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error("Network error");
                    }
                    return response.text();
                })
                .then(function (html) {
                    if (requestId !== currentFetch) {
                        return;
                    }

                    var doc = parseHtml(html);
                    var nextPage = doc.querySelector("[data-browse-page]");
                    if (!nextPage) {
                        throw new Error("Invalid browse response");
                    }

                    if (keepSearchFocus) {
                        var currentResults = page.querySelector("[data-browse-results-panel]");
                        var nextResults = nextPage.querySelector("[data-browse-results-panel]");
                        var currentListOrEmpty;
                        var nextListOrEmpty;
                        var currentPagination = page.querySelector("[data-browse-pagination]");
                        var nextPagination = nextPage.querySelector("[data-browse-pagination]");

                        if (!currentResults || !nextResults) {
                            throw new Error("Invalid browse partial response");
                        }

                        currentListOrEmpty = currentResults.querySelector("[data-browse-list], .browse-empty-state");
                        nextListOrEmpty = nextResults.querySelector("[data-browse-list], .browse-empty-state");
                        if (!currentListOrEmpty || !nextListOrEmpty) {
                            throw new Error("Invalid browse partial response");
                        }

                        currentListOrEmpty.replaceWith(nextListOrEmpty);
                        if (currentPagination && nextPagination) {
                            currentPagination.replaceWith(nextPagination);
                        } else if (currentPagination) {
                            currentPagination.parentNode.removeChild(currentPagination);
                        } else if (nextPagination) {
                            page.querySelector(".browse-main").appendChild(nextPagination);
                        }
                        list = page.querySelector("[data-browse-list]");

                        if (pushHistory && window.history && window.history.pushState) {
                            window.history.pushState({ browseAjax: true }, "", url.toString());
                        }

                        return;
                    }

                    page.replaceWith(nextPage);
                    if (pushHistory && window.history && window.history.pushState) {
                        window.history.pushState({ browseAjax: true }, "", url.toString());
                    }

                    setupBrowsePage(nextPage);
                })
                .catch(function () {
                    return fetch(url.toString(), {
                        method: "GET",
                        credentials: "same-origin",
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error("Browse fallback failed");
                            }
                            return response.text();
                        })
                        .then(function (html) {
                            if (requestId !== currentFetch) {
                                return;
                            }

                            var doc = parseHtml(html);
                            var nextPage = doc.querySelector("[data-browse-page]");
                            if (!nextPage) {
                                throw new Error("Invalid browse fallback response");
                            }

                            page.replaceWith(nextPage);
                            if (pushHistory && window.history && window.history.pushState) {
                                window.history.pushState({ browseAjax: true }, "", url.toString());
                            }

                            setupBrowsePage(nextPage);
                        })
                        .catch(function (error) {
                            if (window.console && window.console.warn) {
                                window.console.warn("Browse request failed", error);
                            }
                        });
                })
                .finally(function () {
                    setLoading(false);
                });
        }

        function requestSuggestions(term) {
            if (!suggestBox) {
                return;
            }

            if (!term || term.length < 2) {
                suggestBox.hidden = true;
                suggestBox.innerHTML = "";
                return;
            }

            var url = new URL("browse.php", window.location.href);
            url.searchParams.set("ajax", "1");
            url.searchParams.set("mode", "suggest");
            url.searchParams.set("q", term);

            fetch(url.toString(), {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    if (!payload || !payload.ok) {
                        return;
                    }
                    renderSuggestions(suggestBox, payload);
                })
                .catch(function () {
                    suggestBox.hidden = true;
                    suggestBox.innerHTML = "";
                });
        }

        var debouncedSearchSubmit = debounce(function () {
            if (!searchForm) {
                return;
            }

            var url = buildUrlFromForm(searchForm);
            updateRecentSearches(searchInput ? searchInput.value.trim() : "");
            fetchAndReplace(url, true);
        }, 300);

        var debouncedSuggestions = debounce(function () {
            requestSuggestions(searchInput ? searchInput.value.trim() : "");
        }, 220);

        if (searchForm) {
            searchForm.addEventListener("submit", function (event) {
                event.preventDefault();
                var url = buildUrlFromForm(searchForm);
                updateRecentSearches(searchInput ? searchInput.value.trim() : "");
                fetchAndReplace(url, true);
            });
        }

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                debouncedSearchSubmit();
                debouncedSuggestions();
            });

            searchInput.addEventListener("focus", function () {
                debouncedSuggestions();
            });
        }

        if (suggestBox) {
            suggestBox.addEventListener("click", function (event) {
                var termBtn = event.target.closest("[data-browse-suggest-term]");
                var categoryBtn = event.target.closest("[data-browse-suggest-category]");
                if (termBtn && searchInput) {
                    searchInput.value = termBtn.getAttribute("data-browse-suggest-term") || "";
                    suggestBox.hidden = true;
                    suggestBox.innerHTML = "";
                    if (searchForm) {
                        var url = buildUrlFromForm(searchForm);
                        fetchAndReplace(url, true);
                    }
                    return;
                }

                if (categoryBtn) {
                    var categoryUrl = new URL(window.location.href);
                    categoryUrl.searchParams.set(
                        "id_category",
                        categoryBtn.getAttribute("data-browse-suggest-category") || "0",
                    );
                    categoryUrl.searchParams.delete("page");
                    fetchAndReplace(categoryUrl, true);
                }
            });
        }

        if (filterForm) {
            filterForm.addEventListener("submit", function (event) {
                event.preventDefault();
                var url = buildUrlFromForm(filterForm);
                fetchAndReplace(url, true);
            });

            filterForm.addEventListener("change", function (event) {
                var target = event.target;
                if (!target || (target.tagName !== "INPUT" && target.tagName !== "SELECT")) {
                    return;
                }
                var url = buildUrlFromForm(filterForm);
                fetchAndReplace(url, true);
            });
        }

        page.addEventListener("click", function (event) {
            var link = event.target.closest("a");
            if (!link || !sameBrowseLink(link.getAttribute("href") || "")) {
                return;
            }

            event.preventDefault();
            fetchAndReplace(new URL(link.getAttribute("href"), window.location.href), true);
        });

        for (var b = 0; b < viewButtons.length; b++) {
            viewButtons[b].addEventListener("click", function () {
                setView(this.getAttribute("data-browse-view") || "compact", true);
            });
        }

        if (list) {
            var initialView = list.getAttribute("data-view") || "compact";
            var storedView = "";

            try {
                storedView = window.localStorage.getItem(storageKey) || "";
            } catch (error) {
                storedView = "";
            }

            if (!readViewExplicit() && (storedView === "compact" || storedView === "full")) {
                initialView = storedView;
            }

            setView(initialView, false);
        }

        syncFilterMoreLabels();

        document.addEventListener("click", function (event) {
            if (!suggestBox || suggestBox.hidden) {
                return;
            }
            if (
                event.target.closest("[data-browse-suggest]") ||
                event.target.closest("[data-browse-search-input]")
            ) {
                return;
            }
            suggestBox.hidden = true;
        });

        window.onpopstate = function () {
            fetchAndReplace(new URL(window.location.href), false);
        };
    }

    document.addEventListener("DOMContentLoaded", function () {
        setupBrowsePage(document.querySelector("[data-browse-page]"));
    });
})();
