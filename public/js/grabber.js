////////////////////////////////////////////////////
//GrabberInfo 
//Powered by jenaDI
////////////////////////////////////////////////////

//Start Grabber
function startGrabberInfo()
{
	//Определяем название
	var name  = $("input[name$='name']").val();
	
	//Определяем описание
	var descr  = $("#descr").val();
	if(name == '' && descr != '')
	{
		return false;
	}
	
	
	
	//ajax request
	$.ajax({
		url: 'upload.grabber.php',  //url  take file
		dataType : "html",   		// тип загружаемых данных
		type: "POST" ,  //post-запрос
		data: {'name' : name}, //Данные
		cache:false,
		beforeSend: function(){
				$("#loadGrabber").html("<img src=\"images/load.gif\" alt=\"Загрузка\">"); //Загрузка 
		},
		error: function(){
				$("#loadGrabber").empty(); //Убираем загрузку
				alert("Произошла ошибка при передаче ajax-запроса");
		},
		success: function(data) {
				$("#loadGrabber").empty(); //Убираем загрузку
				$("#resultGrabber").show('slow');
				$("#resultGrabber").html(data); //Выводим результат	
					
		}, 
	});
	
}

//Checked Start
function checkStart(type)
{
	//Определяем название
	var name  = $("input[name$='name']").val();
	
	//Определяем описание
	var descr  = $("#descr").val();
	
	//Проверяем имя и описание
	if(name == '' && descr != '')
	{
		return false;
	}
	
	//Выводим предложение 
	if(type == null)
	{
		$("#resultGrabber").html('Найти описание <input type="button" value="Да" onClick="checkStart(\'yes\');"> <input type="button" value="Нет" onClick="checkStart(\'no\');">');
		$("#resultGrabber").show('slow');
		return;
	}	
	
	//Да 
	if(type == 'yes')
	{
		$("#resultGrabber").hide('slow');
		$("#resultGrabber").empty();
		startGrabberInfo();
		return;
	}	
	
	//Нет 
	if(type == 'no')
	{
		$("#resultGrabber").hide('slow');
		$("#resultGrabber").empty();
		return;
	}	
}