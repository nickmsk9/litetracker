<?
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Файловый кеш
===================================================================
*/

class Filecache {


	var $dir = null;
	var $type = null;
	var $timeout = null;
	
	
	//construct
	function __construct() { 
		global $config;
		
		$this->dir = $config['filecache']['dir'];
		$this->type = $config['filecache']['type'];
		$this->timeout = $config['filecache']['timeout'];
	}
		
	
	//Получение списка
	function get($file) {
		global $config;
		
		if(!$config['filecache']['use'] )  {
			return false;
		}
		
		$shell = $this->dir.$file.$this->type;
		$time = $this->timeout;
		
		if(file_exists($shell) && is_readable($shell)  && filesize($shell) > 0 && (time() - $time < filemtime($shell))) {
			return unserialize(file_get_contents($shell));
		} else {
			return false;
		}		
	}
	
	//Запись
	function set($file, $data, $flagsOrExpiration = 0, $expiration = 0) {
		global $config;

		if(!$config['filecache']['use'] )  {
			return false;
		}
		
		$shell = $this->dir.$file.$this->type;
		
		if (file_exists($shell )) {
			if (is_writable($shell )) {
				file_put_contents($shell , serialize($data));
			}	
		}
		else {
			$fh = fopen($shell,'w+');
			fwrite($fh, serialize($data));
			fclose($fh);
		}

		return true;
	}
	
	//Удаление
	function delete($file  , $time = 0) {
		$shell = $this->dir.$file.$this->type;
		if (file_exists($shell)) 
			return unlink($shell);
		else
			return false;
	}
	
	
}
?>
