$(function() {
	/*
	количество наборов полей
	*/
	var fieldsetCount = $('#formElem').children().length;
	
	/*
	текущая позиция / ссылка навигации
	*/
	var current 	= 1;
    
	/*
	складываем и сохраняем ширину каждого набора полей
	устанавливаем общую сумму в качестве ширины элемента шагов
	*/
	var stepsWidth	= 0;
    var widths 		= new Array();
	$('#steps .step').each(function(i){
        var $step 		= $(this);
		widths[i]  		= stepsWidth;
        stepsWidth	 	+= $step.width();
    });
	$('#steps').width(stepsWidth);
	
	/*
	Для исключения проблем с IE, устанавливаем фокус ввода в первое полее в форме
	*/
	$('#formElem').children(':first').find(':input:first').focus();	
	
	/*
	показываем навигационную линейку
	*/
	$('#navigation').show();
	
	/*
	При нажатии на ссылке навигации
	форма проскальзывает к соответствующему набору полей
	*/
    $('#navigation a').bind('click',function(e){
		var $this	= $(this);
		var prev	= current;
		$this.closest('ul').find('li').removeClass('selected');
        $this.parent().addClass('selected');
		/*
		сохраняем позицию в текущей переменной
		*/
		current = $this.parent().index() + 1;
		/*
		анимация / проскальзываем к соответствующему набору полей
		Порядок ссылок навигации соответствует
		порядку наборов полей ввода.
		Также, после проскальзывания, переключаем фокус ввода на первый
		элемент ы наборе полей ввода.
		Если нажата последняя ссылка (подтверждение), то мы проверяем все наборы полей,
		иначе проверяется только предыдущий набор
		*/
        $('#steps').stop().animate({
            marginLeft: '-' + widths[current-1] + 'px'
        },500,function(){
			if(current == fieldsetCount)
				validateSteps();
			else
				validateStep(prev);
			$('#formElem').children(':nth-child('+ parseInt(current) +')').find(':input:first').focus();	
		});
        e.preventDefault();
    });
	
	/*
	нажатие на закладке (на последнем элементе ввода в каждом наборе полей), приводит к 
	переходу на следующий шаг
	*/
	$('#formElem > fieldset').each(function(){
		var $fieldset = $(this);
		$fieldset.children(':last').find(':input').keydown(function(e){
			if (e.which == 9){
				$('#navigation li:nth-child(' + (parseInt(current)+1) + ') a').click();
				/* включаем blur для проверки */
				$(this).blur();
				e.preventDefault();
			}
		});
	});
	
	/*
	ошибка всех наборов полей записываются в $('#formElem').data()
	*/
	function validateSteps(){
		var FormErrors = false;
		for(var i = 1; i < fieldsetCount; ++i){
			var error = validateStep(i);
			if(error == -1)
				FormErrors = true;
		}
		$('#formElem').data('errors',FormErrors);	
	}
	
	/*
	проверяем один набор полей ввода,
	Если ошибки есть - возвращаем -1, если ошибок нет -  1
	*/
	function validateStep(step){
		if(step == fieldsetCount) return;
		
		var error = 1;
		var hasError = false;
		$('#formElem').children(':nth-child('+ parseInt(step) +')').find(':input:not(button)').each(function(){
			var $this 		= $(this);
			var valueLength = jQuery.trim($this.val()).length;
			
			if(valueLength == ''){
				hasError = true;
				$this.css('background-color','#FFEDEF');
			}
			else
				$this.css('background-color','#FFFFFF');	
		});
		var $link = $('#navigation li:nth-child(' + parseInt(step) + ') a');
		$link.parent().find('.error,.checked').remove();
		
		var valclass = 'checked';
		if(hasError){
			error = -1;
			valclass = 'error';
		}
		$('<span class="'+valclass+'"></span>').insertAfter($link);
		
		return error;
	}
	
	/*
	В случае наличи ошибок нельзя отправить формы
	*/
	$('#registerButton').bind('click',function(){
		if($('#formElem').data('errors')){
			alert('Пожалуйста, исправьте ошибки в форме!');
			return false;
		}	
	});
});