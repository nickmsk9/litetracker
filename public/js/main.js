//Слайдер
function sliderElement(element, speed) {
    $("html, body").animate(
        {
            scrollTop: $(element).offset().top,
        },
        speed,
    );
}

//Меняем теги
function tag_module() {
    var container = document.getElementById("tags_module");
    if (!container) {
        return;
    }
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "ajax/tags.php", true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) {
            return;
        }
        container.style.opacity = "0";
        setTimeout(function () {
            container.innerHTML = xhr.responseText || "";
            container.style.opacity = "1";
        }, 300);
    };
    xhr.send();
}

//Добавление голоса
function set_rating(id, type, rate_up, rate_down) {
    var ratingStatus = document.getElementById("rating_status");
    var ratingNum = document.getElementById("rating_num");
    var ratingPlus = document.getElementById("rating_plus");
    var ratingMinus = document.getElementById("rating_minus");

    var xhr = new XMLHttpRequest();
    xhr.open(
        "GET",
        "ajax/rating.php?act=set&id_torrent=" +
            encodeURIComponent(id) +
            "&to=" +
            encodeURIComponent(type),
        true,
    );
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) {
            return;
        }
        if (ratingStatus) {
            ratingStatus.innerHTML = xhr.responseText || "";
        }
        if (ratingPlus) {
            ratingPlus.innerHTML = '<img src="public/images/edit_add.png">';
        }
        if (ratingMinus) {
            ratingMinus.innerHTML = '<img src="public/images/messagebox_critical.png">';
        }
        if (ratingNum) {
            var num = type === "up" ? rate_up - rate_down + 1 : rate_up - rate_down - 1;
            ratingNum.innerHTML = num;
        }
    };
    xhr.send();
}

// BBCode toolbar
(function () {
    function wrapSelection(textarea, open, close) {
        var start = textarea.selectionStart || 0;
        var end = textarea.selectionEnd || 0;
        var val = textarea.value || "";
        var selected = val.slice(start, end);
        var replacement = open + selected + close;
        textarea.value = val.slice(0, start) + replacement + val.slice(end);
        textarea.focus();
        var cursor = start + open.length + selected.length + close.length;
        if (typeof textarea.setSelectionRange === "function") {
            if (selected.length > 0) {
                textarea.setSelectionRange(
                    start + open.length,
                    start + open.length + selected.length,
                );
            } else {
                textarea.setSelectionRange(cursor - close.length, cursor - close.length);
            }
        }
    }

    function buildBBToolbar(textarea) {
        var buttons = [
            { label: "B", title: "Жирный", open: "[b]", close: "[/b]" },
            { label: "I", title: "Курсив", open: "[i]", close: "[/i]" },
            { label: "U", title: "Подчёркнутый", open: "[u]", close: "[/u]" },
            { label: "S", title: "Зачёркнутый", open: "[s]", close: "[/s]" },
            { label: "URL", title: "Ссылка", open: "[url=https://]", close: "[/url]" },
            { label: "IMG", title: "Изображение", open: "[img]", close: "[/img]" },
            { label: "Цитата", title: "Цитата", open: "[quote]", close: "[/quote]" },
            { label: "Код", title: "Код", open: "[code]", close: "[/code]" },
        ];
        var bar = document.createElement("div");
        bar.className = "lt-bb-toolbar";
        buttons.forEach(function (btn) {
            var b = document.createElement("button");
            b.type = "button";
            b.className = "lt-bb-btn";
            b.textContent = btn.label;
            b.title = btn.title;
            b.addEventListener("click", function () {
                wrapSelection(textarea, btn.open, btn.close);
            });
            bar.appendChild(b);
        });
        return bar;
    }

    function initBBToolbars() {
        var textareas = document.querySelectorAll(".news-editor-textarea");
        Array.prototype.forEach.call(textareas, function (ta) {
            if (ta.getAttribute("data-bb-ready") === "1") {
                return;
            }
            ta.setAttribute("data-bb-ready", "1");
            var toolbar = buildBBToolbar(ta);
            ta.parentNode.insertBefore(toolbar, ta);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initBBToolbars);
    } else {
        initBBToolbars();
    }
})();
