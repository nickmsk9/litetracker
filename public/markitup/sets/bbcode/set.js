// ----------------------------------------------------------------------------
// markItUp!
// ----------------------------------------------------------------------------
// Copyright (C) 2008 Jay Salvat
// http://markitup.jaysalvat.com/
// ----------------------------------------------------------------------------
// BBCode tags example
// http://en.wikipedia.org/wiki/Bbcode
// ----------------------------------------------------------------------------
// Feel free to add more tags
// ----------------------------------------------------------------------------
mySettings = {
	previewParserPath:	'/preview.php',
    previewAutoRefresh:		false,
	markupSet: [
		{name:'Жирный текст',className:"b", openWith:'[b]', closeWith:'[/b]'},
		{name:'Наклонный такст',className:"i", openWith:'[i]', closeWith:'[/i]'},
		{name:'Подчеркнутый текст',className:"u", openWith:'[u]', closeWith:'[/u]'},
		{name:'Перечеркнутый текст',className:"s", openWith:'[s]', closeWith:'[/s]'},
		{separator:'---------------' },
		{name:'Вставить картинку',className:"img",replaceWith:'[img][![Введите url картинки]!][/img]'},
		{name:'Вставить ссылку',className:"url", openWith:'[url=[![Введите url ссылки]!]]', closeWith:'[/url]', placeHolder:'ссылка'},
		{separator:'---------------' },
		{name:'Выравнивание', className:'alignments', dropMenu:[
			{name:'По левому краю',className:"left", openWith:'[left]', closeWith:'[/left]'},
			{name:'По центру',className:"center", openWith:'[center]', closeWith:'[/center]'},
			{name:'По правому краю',className:"right", openWith:'[right]', closeWith:'[/right]'},
			{name:'По ширине',className:"justify", openWith:'[justify]', closeWith:'[/justify]'}
			]
		},
		{name:'Размер шрифта',className:"size", openWith:'[size=[![Введите размер шрифта]!]]', closeWith:'[/size]',
		dropMenu :[
			{name:'Большой', openWith:'[size=24]', closeWith:'[/size]' },
			{name:'Нормальный', openWith:'[size=12]', closeWith:'[/size]' },
			{name:'Маленький', openWith:'[size=8]', closeWith:'[/size]' }
		]},
		{name:'Colors',className:'colors',openWith:'[color=[![Color]!]]',closeWith:'[/color]',dropMenu: [
					{name:'Yellow',	openWith:'[color=yellow]', 	closeWith:'[/color]', className:"col1-1" },
					{name:'Maroon',	openWith:'[color=maroon]', 	closeWith:'[/color]', className:"col1-2" },
					{name:'Red', 	openWith:'[color=red]', 	closeWith:'[/color]', className:"col1-3" },
					
					{name:'Blue', 	openWith:'[color=blue]', 	closeWith:'[/color]', className:"col2-1" },
					{name:'Teal', openWith:'[color=teal]', 	closeWith:'[/color]', className:"col2-2" },
					{name:'Green', 	openWith:'[color=green]', 	closeWith:'[/color]', className:"col2-3" },
					
					{name:'White', 	openWith:'[color=white]', 	closeWith:'[/color]', className:"col3-1" },
					{name:'Gray', 	openWith:'[color=gray]', 	closeWith:'[/color]', className:"col3-2" },
					{name:'Black',	openWith:'[color=black]', 	closeWith:'[/color]', className:"col3-3" }
		]},
		{separator:'---------------' },
		{name:'Маркерованный список',className:"li", openWith:'[li] '},
		{name:'Разделительная линия',className:"hr", openWith:'[hr]\n'},
		
		{separator:'---------------' },
		{name:'Цитата',className:"quote", openWith:'[quote]', closeWith:'[/quote]'},
		{name:'Код',className:"code", openWith:'[code]', closeWith:'[/code]'}, 
		{name:'Спойлер',className:"spoiler", openWith:'[spoiler]', closeWith:'[/spoiler]'}, 
		{separator:'---------------' },
		{name:'Видео RuTube',className:"rutube", openWith:'[rutube]', closeWith:'[/rutube]'}, 
		{name:'Видео YouTube',className:"youtube", openWith:'[youtube]', closeWith:'[/youtube]'}, 

		{separator:'---------------' },
		{name:'Просмотр', className:"preview", call:'preview' },
		{name:'Удалить просмотр', className:"previewc", replaceWith:function() { 
				jQuery(".markItUpPreviewFrame").remove();
					} }
	]
}