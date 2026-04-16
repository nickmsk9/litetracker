///////////////////////////////////////////////////////////////////////
//Комментирование
///////////////////////////////////////////////////////////////////////

//Возврат к форме
function upCommentForm() {
	$("textarea[name='textComment']").val('');
	$('#addComment').slideDown(350,function(){
		sliderElement('#addComment' , 800);
	});	
	return false;	
}

//Возврат к форме
function downCommentForm() {
	
	if($("#addComment").is(":visible")) {
		$('#addComment').slideUp(350,function(){
			sliderElement('#setComment' , 800);
		});	
	}
	return false;	
}

function replyWallComment(userName) {
	var field = document.getElementById('wall-comment-text');
	if (!field) {
		return false;
	}

	var prefix = '[b]' + userName + '[/b], ';
	if (field.value.indexOf(prefix) !== 0) {
		field.value = prefix + field.value;
	}

	field.focus();
	return false;
}

