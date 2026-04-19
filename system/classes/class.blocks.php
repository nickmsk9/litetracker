<?
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Блочная система
===================================================================
*/

class blocks {

	private $_a;	//Конфиг
	private $_dump = array(); //Массив инклудных блоков , которые позже выводятся

	/*
	 *	Конструктор
	*/
	function __construct()  {
		global $blocks;
		$this->_a  = $blocks;
	}

	/*
	 *	Вывод блока
	*/
	function display($type , $align = '')  {
		global $config , $language , $USER , $db , $memcache , $timer ,$PRIV , $rewrite , $CRON  , $blocks;


		//Проверяем , используются ли они
		if(!$this->_a[$type]['use'] or !$this->_a['use']) {
			return false;
		}


		//Перебираем все блоки
		foreach($this->_a[$type]['block'] AS $a) {

			//Если данный блок включен и находится в той или иной области
			if($a['visible'] && ($a['align'] == $align) ) {
				//Ищем игнорные файлы
				if(!empty($a['ignore']) ) {

					$file_ignore = str_replace("/" , "" , $_SERVER['PHP_SELF']);
					if(substr_count($a['ignore'] , $file_ignore) > 0 ) {
						continue;
					}
				}



				//Записываем в переменную вывод блока
				$file = $_SERVER['DOCUMENT_ROOT'].'/blocks/'.$a['file'];
				// ob_start();

				//Проверяем файл
				if(is_file($file) ) {
					require $file;
				} else  {
					echo '<div>[#'.$a['name'].'] Error 404</div>';
				}

				// $this->_dump[$type][$align][] = ob_get_contents();

			}


		}

		//Вывод блоков
		// echo implode('' , $this->_dump[$type][$align]);
	}
}
?>
