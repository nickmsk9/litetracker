//Слайдер
function sliderElement(element , speed) 
{
	$('html, body').animate({
			scrollTop: $(element).offset().top
	}, speed);
}

//Меняем теги
function tag_module() {
	$.get('../../ajax/tags.php' ,  {}, function(response) {
			$("#tags_module").fadeOut('300' , function() {
				$("#tags_module").html(response);
				$("#tags_module").fadeIn('300');
			});
            
			
    },  'html');
}


//Добавление голоса
function set_rating(id , type , rate_up , rate_down) {
	//, 'rating_up' : rate_up , 'rating_down' : rate_down
	$.get('../../ajax/rating.php' ,  {'act' : 'set' , 'id_torrent' : id , 'to' : type }, function(response) {
				$("#rating_status").html(response);
				$("#rating_plus").html('<img src="public/images/edit_add.png">');
				$("#rating_minus").html('<img src="public/images/messagebox_critical.png">');
				
				var rating_num_real = rate_up - rate_down;
				if(type == 'up') {
					rating_num_real += 1;
				} else {
					rating_num_real += 1 * (-1);
						
				}
				
				$("#rating_num").html(rating_num_real);
				
    },  'html');
}

// BBCode toolbar
(function () {
  function wrapSelection(textarea, open, close) {
    var start = textarea.selectionStart || 0;
    var end = textarea.selectionEnd || 0;
    var val = textarea.value || '';
    var selected = val.slice(start, end);
    var replacement = open + selected + close;
    textarea.value = val.slice(0, start) + replacement + val.slice(end);
    textarea.focus();
    var cursor = start + open.length + selected.length + close.length;
    if (typeof textarea.setSelectionRange === 'function') {
      if (selected.length > 0) {
        textarea.setSelectionRange(start + open.length, start + open.length + selected.length);
      } else {
        textarea.setSelectionRange(cursor - close.length, cursor - close.length);
      }
    }
  }

  function buildBBToolbar(textarea) {
    var buttons = [
      { label: 'B', title: 'Жирный', open: '[b]', close: '[/b]' },
      { label: 'I', title: 'Курсив', open: '[i]', close: '[/i]' },
      { label: 'U', title: 'Подчёркнутый', open: '[u]', close: '[/u]' },
      { label: 'S', title: 'Зачёркнутый', open: '[s]', close: '[/s]' },
      { label: 'URL', title: 'Ссылка', open: '[url=https://]', close: '[/url]' },
      { label: 'IMG', title: 'Изображение', open: '[img]', close: '[/img]' },
      { label: 'Цитата', title: 'Цитата', open: '[quote]', close: '[/quote]' },
      { label: 'Код', title: 'Код', open: '[code]', close: '[/code]' },
    ];
    var bar = document.createElement('div');
    bar.className = 'lt-bb-toolbar';
    buttons.forEach(function (btn) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'lt-bb-btn';
      b.textContent = btn.label;
      b.title = btn.title;
      b.addEventListener('click', function () {
        wrapSelection(textarea, btn.open, btn.close);
      });
      bar.appendChild(b);
    });
    return bar;
  }

  function initBBToolbars() {
    var textareas = document.querySelectorAll('.news-editor-textarea');
    Array.prototype.forEach.call(textareas, function (ta) {
      if (ta.getAttribute('data-bb-ready') === '1') { return; }
      ta.setAttribute('data-bb-ready', '1');
      var toolbar = buildBBToolbar(ta);
      ta.parentNode.insertBefore(toolbar, ta);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBBToolbars);
  } else {
    initBBToolbars();
  }
})();
