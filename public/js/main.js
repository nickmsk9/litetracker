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
