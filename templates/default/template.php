<?

if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}
	


  
  
 function begin_frame($caption = "", $width = "100", $center = false, $padding = 10)
  {
    $tdextra = "";
   ?>
 
 <div align="center" style="padding: 10px;">
 <div class="mbord">
   <table width="100%">
	<tbody><tr><td align="left">
   <?

  }


  function end_frame()
  {
    ?>
	</td>
	</tr></tbody></table>
  </div></div>

	<?
  }
  
?>