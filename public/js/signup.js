/*
===========================================
Регистрация
===========================================
*/

//Проверка имени
function checkName() {
	var name = $("input[name$='name']").val();
	if(name == '' || name.length <= 2) {
		$('#checkName').html('<div id="ajaxerror">Имя не введено</div>');
		$("#checkName").fadeIn(400);
		return 0;
	}else{
		$('#checkName').html('<div id="ajaxsuccess">Отлично</div>');
		$("#checkName").fadeIn(400);
		return 1;
	}	
}

//Проверка почты
function checkEmail() { 
	var email = /^\w+([\.-]?\w+)*@(((([a-z0-9]{2,})|([a-z0-9][-][a-z0-9]+))[\.][a-z0-9])|([a-z0-9]+[-]?))+[a-z0-9]+\.([a-z]{2}|(com|net|org|edu|int|mil|gov|arpa|biz|aero|name|coop|info|pro|museum))$/i;
	var input = $("input[name$='email']").val(); 
	if(!email.test(eval("document.forms['signup'].email.value"))) {
		$('#checkEmail').html('<div id="ajaxerror">Не верный формат</div>');
		$("#checkEmail").fadeIn(400);
		return 0;
	}else {
		$('#checkEmail').html('<div id="ajaxsuccess">Отлично</div>');
		$("#checkEmail").fadeIn(400);
		return 1; 
	} 
}

//Проверка пароля
function checkPassword() { 
	var password = $("input[name$='password']").val();
	if(password == '') {
		$('#checkPassword').html('<div id="ajaxerror">Введите пароль</div>');
		$("#checkPassword").fadeIn(400);
		return 0;
	}

	$('#checkPassword').html('<div id="ajaxsuccess">Отлично</div>');
	$("#checkPassword").fadeIn(400);
	return 1;	
}

//Общая проверка
function sendSignup() {
	var name = checkName();
	var password = checkPassword();
	var email = checkEmail();
	if(!name || !password || !email) {
		return 0;
	}else{
		return document.getElementById('signup').submit();
	}	
}
