<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Редактор
===================================================================
*/

/*
 * Виджет "Мне нравится"
*/
function vkontakte_like($title , $desc, $id) {
	global $config;



	// $title = htmlspecialchars($title);
	if($config['vkontakte_use'] && $config['vkontakte_like']) {
		echo <<<HTML
		<div id="vk_like" style="float:left"></div>
<script type="text/javascript">
window.onload = function () {
 VK.Widgets.Like('vk_like', {width: 500, pageTitle: '{$title}'}, {$id});
}
</script>
HTML;
	}

	// , pageDescription: '{$desc}'
}

/*
 *	Виджет ремомендации
*/
function vkontakte_recommended() {
	global $config;

	if($config['vkontakte_use'] && $config['vkontakte_recommended']) {
		echo <<<HTML
		<div id="vk_recommended"></div>
		<script type="text/javascript">
		VK.Widgets.Recommended("vk_recommended", {limit: {$config['vkontakte_recommended_limit']}});
		</script>
HTML;
	}
}

/*
 *	Виджет сообществ
*/
function vkontakte_groups() {
	global $config;


	if($config['vkontakte_use'] && $config['vkontakte_groups']) {
		echo <<<HTML
		<!-- VK Widget -->
		<div id="vk_groups"></div>
		<script type="text/javascript">
		VK.Widgets.Group("vk_groups", {mode: {$config['vkontakte_groups_mode']} , width: "auto", height: "auto"}, {$config['vkontakte_groups_key']});
		</script>
HTML;
	}
}

/*
 *	Виджет сохранить
*/
function vkontakte_save() {
	global $config;
	if($config['vkontakte_use'] && $config['vkontakte_save']) {
		echo <<<HTML
		<!-- Put this script tag to the place, where the Share button will be -->
		<script type="text/javascript"><!--
		document.write(VK.Share.button('{$config['site_url']}',{type: "round", text: "{$config['vkontakte_save_text']}"}));
		--></script>
HTML;
	}
}


/*
 *	Вход через Вконтакте
*/
function vkontakte_login() {
	global $config;
	if($config['vkontakte_use'] && $config['vkontakte_login']) {
		echo <<<HTML
			<!-- Put this div tag to the place, where Auth block will be -->
			<div id="vk_auth"></div>
			<script type="text/javascript">
			VK.Widgets.Auth("vk_auth", {width: "200px", authUrl: '/login.php'});
			</script>
HTML;
	}
}


function vkontakte_profile($id) {
	global $config;

		if($config['vkontakte_use'] && $config['vkontakte_profile'] ) {
			echo <<<HTML
			<!-- VK Widget -->
			<div id="vk_profile"></div>
			<script type="text/javascript">
			VK.Widgets.Group("vk_profile", {mode: 2 , width: "auto", height: "auto"}, -{$id});
			</script>
HTML;
		}
}
?>
