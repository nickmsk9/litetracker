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


