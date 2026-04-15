/*
 * Опросы
*/
function loadpoll() {
	$("#loading_poll").html("Загрузка опросика...");
	$("#loading_poll").fadeIn("fast");
	$("#poll_container").fadeIn("slow", function () {
	 
	  $.post("../../ajax/poll.php", {'do':"load"}, function (r){ $("#poll_container").html(r); 
		if($("#results").hasClass("results"))
		{
			$("div[id='poll_result']").each(function(){
				var percentage = $(this).attr("name");
				
				$(this).css({width: "0%"}).animate({
				width: percentage+"%"}, 1600);
				
				});
		 $("#loading").fadeOut("fast"); 		
		}
	 
	},"html" );});
}

function addvote(val) {
	$("#question_select").val(val);
	$("#poll_button").show("fast");
}

function vote() {
	var poll_id = $("#poll_id").val();
	var question = $("#question_select").val();
	$("#poll_container").empty();
	$("#poll_container").append("<div id=\"loading_poll\" style=\"display:none\"><\/div>");
	$("#loading_poll").fadeIn("fast", function () {$("#loading_poll").html("Ждите...Голос отправляется...");});
	
		$.post("../../ajax/poll.php",{'do':"voting",'poll_id':poll_id,'question':question}, function(r) {
			if(r.status == 0 ) {
				$("#loading_poll").fadeIn("fast", function () {$("#loading_poll").empty(); $("#loading_poll").html(r.msg);});
			} else if(r.status == 1 ) {
				$("#loading_poll").empty();
				loadpoll();
			}
		} ,"json");
}