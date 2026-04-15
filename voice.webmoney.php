<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Оплата через Webmoney
===================================================================
*/


//Подключаем главный системный файл
require 'system/init.php';

//Проверяем пользователя
is_login();


//Заголовок
head('Оплата через Webmoney');
begin_frame('Оплата через Webmoney');
msg('Вы можете обменять деньги Webmoney , на деньги нашего сайта' , 'Это позволит вам оплачивать услуги на нашем трекере');
?>



<form action="https://merchant.webmoney.ru/lmi/payment.asp" method="POST">
<table width="50%" align="center"> 
<tr>
<td colspan="2">
<b>1 рубль = 1 WMR</b>
</td>
</tr>
<tr>
<td>Сумма:</td><td><input type="text" name="LMI_PAYMENT_AMOUNT" value="10"> WMR<br><small>Выберите сумму, которую хотите внести . Формат: 10.0 </small></td>
</tr>
</table>

<input type="hidden" name="LMI_PAYMENT_DESC" value="Обмен WMR на деньги трекера">
<input type="hidden" name="LMI_PAYEE_PURSE" value="<?=$config['wmr_number'];?>">
<input type="submit" value="Оплатить">
</form>



<?
end_frame();
//Подвал
foot();


?>