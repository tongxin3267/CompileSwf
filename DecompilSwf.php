<?php 
error_reporting(E_ALL);
set_time_limit(0);

require('framework/SwfIO.php');
require('framework/ExtractItem.php');

class DecompilSwf
{
	const DIRCOMP = 'swf_decompiler/';
	const DIRDECOMP = 'decompile/';
	
	private $_dirswf;
	private $_swfname;
	
	private $_nameclass;
	private $_xmls;
	private $_pngs;
	private $_jpegs;
	
	private $_swf;
	
	public function __construct($swfname)
	{
		system("title Decompile swf");
		
		$this->_dirswf = self::DIRDECOMP.$swfname."/";
		
		$this->_swfname = $swfname;
	}
	
	public function Start()
	{
		$this->InitSWF();
		$this->ExtractFiles();
	}
	
	private function InitSWF()
	{
		$dirswf = $this->_dirswf;
		if(!is_dir($dirswf))
			mkdir($dirswf);

		$data = file_get_contents(self::DIRCOMP.$this->_swfname.".swf");

		$this->_swf = new ExtractItem($data);
	}
	
	private function ExtractFiles()
	{
		$swf = $this->_swf;
		foreach ($swf->files["binaries"] as $bin) {
			$binname = $swf->GetSymbolName($bin["id"]);
			
			$data = $bin["data"];
			
			file_put_contents($this->_dirswf.$binname.".dat", $data);
		}
		
		foreach ($swf->files["images"] as $img) {
			$imgname = $swf->GetSymbolName($img["id"]);
			
			$data = $img["content"];
			
			file_put_contents($this->_dirswf.$imgname.".png", $data);
		}
	}
	
}

$dossier=opendir("swf_decompiler");

while (false != ($file = readdir($dossier))) {
	if($file == '.' || $file == '..')
		continue;
	$nameswf = explode('.', $file)[0];
	
	try {
	$DecompilSwf = new DecompilSwf($nameswf);
	$DecompilSwf->Start();
	} catch (Exception $e) {
		echo $e."\n\r";
	}
	
	echo "Decompile de ".$file."\n\r";
}

echo "Fini";