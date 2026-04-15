<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Конфиг ВКонтакте
===================================================================
*/


$config['vkontakte_use'] = 0 ; 				//Включение / Выключение виджетов ВКонтакте
$config['vkontakte_api_id'] = '--' ; 	//Ваш API ID 

//Мне нравится
$config['vkontakte_like'] = 1 ; 

//Рекомендации
$config['vkontakte_recommended'] =  1 ; 
$config['vkontakte_recommended_limit'] = 10 ; //Лимит рекомендаций


//Сообщества
$config['vkontakte_groups'] = 1 ; 
$config['vkontakte_groups_key'] = '0' ; //Номер на страницу ; группу и прочее
$config['vkontakte_groups_mode'] = 0 ; 			//1 - Только название | 2 - Новости | 3 - Участники

//Сохранить
$config['vkontakte_save'] = 1 ; 
$config['vkontakte_save_text'] = 'Сохранить' ; //Текст на кнопке

//Страница ВК в профиле
$config['vkontakte_profile'] = 1;

?>
