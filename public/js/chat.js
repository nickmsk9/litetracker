///////////////////////////////////////////////////////////////////////
//Чат
///////////////////////////////////////////////////////////////////////

//Обновление чата
function update() {
	$.post("../../ajax/chat.php" , {type: 'update'} , function(response) {
		$("#result_chat").html(response);
	}, "html");
	setTimeout("update();", 1000);
}


//Запускаем чат
update();

//Отправка сообщения
function  send(){
		$("button[name$='send']").attr("disabled", true); //Делаем кнопку невидимой
		
		

		//Текст
		// var text = document.chat.text_chat.value;
		var text = $("#text_chat").val();
		
		//Проверяем, введен ли текст
		if(!text) {
			$("button[name$='send']").removeAttr("disabled");
			return false;
		}	
		
		//Отправка данных
		$.post("../../ajax/chat.php" , {'type': 'send', 'text':text} , function(response) {
			$("#result_send").html(response);
			$("button[name$='send']").removeAttr("disabled");
		}, "html");
		
		//Очищаем поле текста
		// document.chat.text_chat.value = '';
		$("#text_chat").val("");
}


//Удаление сообщений
function confirm_message_delete(id) {
       var id = id;
        if (confirm("Удалить сообщение?")) {
            return  message_delete(id);
        }
       return false;
    }



function message_delete(id) {

	var id = id; //ID сообщения
	
	jQuery.post("../../ajax/chat.php" , {'type': 'delete', 'id':id } , function(response) {
		
		jQuery("#message_"+id).fadeOut(100, function(){

			  });
		
	}, "html");
	

}

//Очистка чата
function confirm_clear() {
       
        if (confirm("Очистить чат?")) {
            return  clear();
        }
       return false;
    }

function clear() {


	jQuery.post("../../ajax/chat.php" , {'type': 'clear' } , function(response) {
		
		jQuery("#result_chat").html(response);
		
	}, "html");
	

}
