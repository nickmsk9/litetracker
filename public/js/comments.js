(function () {
    var AJAX_URL = "/ajax/comments.php";
    var noticeTimers = typeof WeakMap === "function" ? new WeakMap() : null;

    function ready(fn) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", fn);
            return;
        }

        fn();
    }

    function closest(el, selector) {
        return el && el.closest ? el.closest(selector) : null;
    }

    function sendAjax(url, formData, onSuccess, onError) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", url || AJAX_URL, true);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.onreadystatechange = function () {
            var payload;

            if (xhr.readyState !== 4) {
                return;
            }

            try {
                payload = JSON.parse(xhr.responseText || "{}");
            } catch (error) {
                if (typeof onError === "function") {
                    onError("Не удалось обработать ответ сервера. Попробуйте обновить страницу.");
                }
                return;
            }

            if (xhr.status >= 200 && xhr.status < 300 && payload && payload.ok) {
                if (typeof onSuccess === "function") {
                    onSuccess(payload);
                }
                return;
            }

            if (typeof onError === "function") {
                onError(payload && payload.message ? payload.message : "Произошла ошибка.");
            }
        };
        xhr.send(formData);
    }

    function confirmAction(message) {
        if (
            window.LiteTracker &&
            window.LiteTracker.ui &&
            typeof window.LiteTracker.ui.confirm === "function"
        ) {
            return window.LiteTracker.ui.confirm(message);
        }

        return window.confirm(message);
    }

    function setButtonBusy(button, busy, text) {
        if (!button) {
            return;
        }

        if (!button.getAttribute("data-original-label")) {
            button.setAttribute("data-original-label", button.value || button.textContent || "");
        }

        button.disabled = !!busy;

        if (text) {
            if ("value" in button) {
                button.value = text;
            } else {
                button.textContent = text;
            }
        } else if (!busy) {
            if ("value" in button) {
                button.value = button.getAttribute("data-original-label") || "Отправить";
            } else {
                button.textContent = button.getAttribute("data-original-label") || "Отправить";
            }
        }
    }

    function CommentThread(root) {
        this.root = root;
        this.type = root.getAttribute("data-comment-type") || "";
        this.objectId = root.getAttribute("data-object-id") || "0";
        this.file = root.getAttribute("data-file") || "";
        this.endpoint = root.getAttribute("data-endpoint") || AJAX_URL;
        this.refreshUrl = root.getAttribute("data-refresh-url") || this.endpoint;
    }

    CommentThread.prototype.notice = function (message, isError) {
        var notice = this.root.querySelector("[data-comment-notice]");

        if (!notice) {
            return;
        }

        notice.textContent = message || "";
        notice.className =
            "comment-ajax-notice" +
            (isError ? " comment-ajax-notice-error" : " comment-ajax-notice-success");
        notice.hidden = !message;

        if (!message) {
            return;
        }

        if (noticeTimers) {
            clearTimeout(noticeTimers.get(notice));
            noticeTimers.set(
                notice,
                setTimeout(function () {
                    notice.hidden = true;
                }, 5000),
            );
            return;
        }

        clearTimeout(notice._hideTimer);
        notice._hideTimer = setTimeout(function () {
            notice.hidden = true;
        }, 5000);
    };

    CommentThread.prototype.stream = function () {
        return this.root.querySelector("[data-comment-stream]");
    };

    CommentThread.prototype.normalizeMainForm = function () {
        var forms = this.root.querySelectorAll("[data-comment-form]");
        var mainForm;
        var i;

        if (!forms.length) {
            return null;
        }

        // Keep only one canonical form per thread: the last form in DOM order.
        mainForm = forms[forms.length - 1];

        for (i = 0; i < forms.length; i += 1) {
            if (forms[i] === mainForm) {
                continue;
            }

            if (forms[i].parentNode) {
                forms[i].parentNode.removeChild(forms[i]);
            }
        }

        if (mainForm.parentNode === this.root && this.root.lastElementChild === mainForm) {
            return mainForm;
        }

        this.root.appendChild(mainForm);
        return mainForm;
    };

    CommentThread.prototype.form = function () {
        return this.normalizeMainForm();
    };

    CommentThread.prototype.textarea = function (root) {
        root = root || this.form() || this.root;
        return (
            root.querySelector("[data-comment-textarea]") ||
            root.querySelector("textarea[name='text']") ||
            root.querySelector("textarea[name='descr']") ||
            root.querySelector("textarea[name='textComment']")
        );
    };

    CommentThread.prototype.focusTextarea = function (root) {
        var textarea = this.textarea(root);

        if (!textarea) {
            return;
        }

        textarea.focus();

        if (typeof textarea.setSelectionRange === "function") {
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        }
    };

    CommentThread.prototype.ensureReplyCancelButton = function (form) {
        var controls;
        var cancelBtn;

        if (!form) {
            return null;
        }

        controls = form.querySelector(".wall-form-controls");
        if (!controls) {
            return null;
        }

        cancelBtn = controls.querySelector("[data-comment-reply-cancel-main]");
        if (cancelBtn) {
            return cancelBtn;
        }

        cancelBtn = document.createElement("button");
        cancelBtn.type = "button";
        cancelBtn.className = "wall-comment-button wall-form-cancel";
        cancelBtn.textContent = "Отмена";
        cancelBtn.setAttribute("data-comment-reply-cancel", "1");
        cancelBtn.setAttribute("data-comment-reply-cancel-main", "1");
        cancelBtn.hidden = true;
        controls.appendChild(cancelBtn);

        return cancelBtn;
    };

    CommentThread.prototype.setReplyMode = function (form, enabled) {
        var replyBanner;
        var replyLabel;
        var cancelBtn;

        if (!form) {
            return;
        }

        replyBanner = form.querySelector("[data-comment-reply-banner]");
        replyLabel = form.querySelector("[data-comment-reply-label]");
        cancelBtn = this.ensureReplyCancelButton(form);

        if (replyLabel) {
            replyLabel.textContent = "";
        }

        if (replyBanner) {
            replyBanner.hidden = true;
        }

        if (cancelBtn) {
            cancelBtn.hidden = !enabled;
        }
    };

    CommentThread.prototype.resetReply = function (form) {
        var parentInput;

        form = form || this.form();
        if (!form) {
            return;
        }

        parentInput = form.querySelector("[data-comment-parent]");

        if (parentInput) {
            parentInput.value = "0";
        }

        this.setReplyMode(form, false);
    };

    CommentThread.prototype.refreshStream = function (streamHtml, highlightId) {
        var stream = this.stream();
        var tmp;
        var newStream;
        var self = this;

        if (!stream || !streamHtml) {
            return;
        }

        tmp = document.createElement("div");
        tmp.innerHTML = streamHtml;
        newStream = tmp.querySelector("[data-comment-stream]");
        stream.innerHTML = newStream ? newStream.innerHTML : streamHtml;
        this.normalizeMainForm();

        if (!highlightId) {
            return;
        }

        setTimeout(function () {
            var el = self.root.querySelector('[data-comment-id="' + highlightId + '"]');

            if (!el) {
                return;
            }

            el.classList.add("comment-entry-highlight", "comment-entry-new");
            setTimeout(function () {
                el.classList.remove("comment-entry-highlight");
            }, 2000);
            el.scrollIntoView({ behavior: "smooth", block: "nearest" });
        }, 30);
    };

    CommentThread.prototype.fillBasePayload = function (formData) {
        if (!formData.has("type")) {
            formData.append("type", this.type);
        }

        if (!formData.has("object_id")) {
            formData.append("object_id", this.objectId);
        }

        if (!formData.has("file")) {
            formData.append("file", this.file);
        }
    };

    CommentThread.prototype.activateReply = function (button) {
        var form = this.form();
        var parentInput;

        if (!form) {
            return;
        }

        parentInput = form.querySelector("[data-comment-parent]");

        if (!parentInput) {
            return;
        }

        parentInput.value = button.getAttribute("data-comment-id") || "0";

        this.setReplyMode(form, true);

        this.focusTextarea(form);
    };

    CommentThread.prototype.buildInlineEditor = function (commentEl) {
        var slot = commentEl.querySelector(".wall-comment-editor-slot");
        var sourceEl = commentEl.querySelector(".wall-comment-source");
        var existing = commentEl.querySelector(".wall-inline-editor");
        var editLink = commentEl.querySelector("[data-wall-edit]");
        var commentId = commentEl.getAttribute("data-comment-id") || "0";
        var csrfToken = editLink ? editLink.getAttribute("data-csrf-token") || "" : "";
        var form;
        var textarea;
        var controls;
        var saveBtn;
        var cancelBtn;
        var self = this;

        if (!slot || !sourceEl) {
            return;
        }

        if (existing) {
            this.focusTextarea(existing);
            return;
        }

        form = document.createElement("form");
        form.className = "wall-inline-editor";

        textarea = document.createElement("textarea");
        textarea.className = "wall-inline-editor-textarea";
        textarea.value = sourceEl.value;
        form.appendChild(textarea);

        controls = document.createElement("div");
        controls.className = "wall-inline-editor-actions";

        saveBtn = document.createElement("button");
        saveBtn.type = "submit";
        saveBtn.className = "wall-form-submit wall-inline-editor-save";
        saveBtn.textContent = "Сохранить";
        controls.appendChild(saveBtn);

        cancelBtn = document.createElement("button");
        cancelBtn.type = "button";
        cancelBtn.className = "wall-comment-button";
        cancelBtn.textContent = "Отмена";
        cancelBtn.addEventListener("click", function () {
            if (form.parentNode) {
                form.parentNode.removeChild(form);
            }
        });
        controls.appendChild(cancelBtn);

        form.appendChild(controls);
        form.addEventListener("submit", function (event) {
            var formData;

            event.preventDefault();

            if (!textarea.value.trim()) {
                self.notice("Введите текст комментария.", true);
                return;
            }

            setButtonBusy(saveBtn, true, "Сохранение...");

            formData = new FormData();
            formData.append("action", "edit");
            formData.append("comment_id", commentId);
            formData.append("text", textarea.value);
            if (csrfToken) {
                formData.append("csrf_token", csrfToken);
            }
            self.fillBasePayload(formData);

            sendAjax(
                self.endpoint,
                formData,
                function (payload) {
                    self.refreshStream(payload.html || "", payload.comment_id || commentId);
                    self.notice(payload.message || "Комментарий обновлён.");
                },
                function (message) {
                    setButtonBusy(saveBtn, false);
                    self.notice(message, true);
                },
            );
        });

        slot.appendChild(form);
        textarea.focus();
    };

    CommentThread.prototype.refresh = function () {
        var formData = new FormData();
        var self = this;

        formData.append("action", "refresh");
        this.fillBasePayload(formData);

        sendAjax(
            this.refreshUrl,
            formData,
            function (payload) {
                self.refreshStream(payload.html || "", payload.comment_id || 0);

                if (payload.message) {
                    self.notice(payload.message);
                }
            },
            function (message) {
                self.notice(message, true);
            },
        );
    };

    CommentThread.prototype.submitAdd = function (form) {
        var textarea = this.textarea(form);
        var submitBtn = form.querySelector('[type="submit"]');
        var formData;
        var self = this;

        if (textarea && !textarea.value.trim()) {
            this.notice("Введите текст комментария.", true);
            this.focusTextarea(form);
            return;
        }

        setButtonBusy(submitBtn, true, "Отправка...");

        formData = new FormData(form);
        formData.set("action", "add");
        this.fillBasePayload(formData);

        sendAjax(
            this.endpoint,
            formData,
            function (payload) {
                var newId = payload.comment_id || 0;

                self.refreshStream(payload.html || "", newId);

                if (textarea) {
                    textarea.value = "";
                }

                self.resetReply(form);
                self.notice(payload.message || "Комментарий добавлен.");
                setButtonBusy(submitBtn, false);
            },
            function (message) {
                self.notice(message, true);
                setButtonBusy(submitBtn, false);
            },
        );
    };

    CommentThread.prototype.submitCommentAction = function (action, button, options) {
        var comment = closest(button, ".wall-comment");
        var commentId = comment ? comment.getAttribute("data-comment-id") || "0" : "0";
        var csrfToken = button.getAttribute("data-csrf-token") || "";
        var formData;
        var self = this;

        options = options || {};

        if (!comment || commentId === "0") {
            return;
        }

        if (options.confirm && !confirmAction(options.confirm)) {
            return;
        }

        formData = new FormData();
        formData.append("action", action);
        formData.append("comment_id", commentId);
        if (csrfToken) {
            formData.append("csrf_token", csrfToken);
        }
        this.fillBasePayload(formData);

        sendAjax(
            this.endpoint,
            formData,
            function (payload) {
                if (payload.html) {
                    self.refreshStream(payload.html || "", payload.comment_id || 0);
                }

                if (action === "delete") {
                    self.resetReply();
                }

                self.notice(payload.message || options.success || "");
            },
            function (message) {
                self.notice(message, true);
            },
        );
    };

    CommentThread.prototype.handleClick = function (event) {
        var target = event.target;
        var replyBtn = closest(target, "[data-comment-reply], [data-wall-reply]");
        var cancelBtn = closest(target, "[data-comment-reply-cancel]");
        var editBtn = closest(target, "[data-wall-edit]");
        var deleteBtn = closest(target, "[data-wall-delete]");
        var reportBtn = closest(target, "[data-wall-report]");
        var refreshBtn = closest(target, "[data-comment-refresh]");
        var comment;

        if (refreshBtn && this.root.contains(refreshBtn)) {
            event.preventDefault();
            this.refresh();
            return;
        }

        if (replyBtn && this.root.contains(replyBtn)) {
            event.preventDefault();
            this.activateReply(replyBtn);
            return;
        }

        if (cancelBtn && this.root.contains(cancelBtn)) {
            event.preventDefault();
            this.resetReply(closest(cancelBtn, "[data-comment-form]"));
            this.focusTextarea(closest(cancelBtn, "[data-comment-form]"));
            return;
        }

        if (editBtn && this.root.contains(editBtn)) {
            event.preventDefault();
            comment = closest(editBtn, ".wall-comment");
            if (comment) {
                this.buildInlineEditor(comment);
            }
            return;
        }

        if (deleteBtn && this.root.contains(deleteBtn)) {
            event.preventDefault();
            this.submitCommentAction("delete", deleteBtn, {
                confirm: "Удалить комментарий?",
                success: "Комментарий удалён.",
            });
            return;
        }

        if (reportBtn && this.root.contains(reportBtn)) {
            event.preventDefault();
            this.submitCommentAction("report", reportBtn, {
                confirm: "Отправить жалобу администрации?",
                success: "Жалоба отправлена.",
            });
        }
    };

    CommentThread.prototype.handleSubmit = function (event) {
        var form = closest(event.target, "[data-comment-form]");

        if (!form || !this.root.contains(form)) {
            return;
        }

        event.preventDefault();
        this.submitAdd(form);
    };

    CommentThread.prototype.bind = function () {
        var self = this;
        var form = this.normalizeMainForm();
        this.setReplyMode(form, false);

        this.root.addEventListener("click", function (event) {
            self.handleClick(event);
        });
        this.root.addEventListener("submit", function (event) {
            self.handleSubmit(event);
        });
    };

    ready(function () {
        var roots = document.querySelectorAll("[data-comment-thread]");
        Array.prototype.forEach.call(roots, function (root) {
            if (root.getAttribute("data-comment-thread-ready") === "1") {
                return;
            }

            root.setAttribute("data-comment-thread-ready", "1");
            new CommentThread(root).bind();
        });
    });

    window.CommentThread = CommentThread;
    window.replyWallComment = function () {
        var root = document.querySelector("[data-comment-thread]");
        var thread = root ? new CommentThread(root) : null;
        var form = thread ? thread.form() : null;

        if (thread) {
            thread.resetReply(form);
            thread.focusTextarea(form);
        }

        return false;
    };
})();
