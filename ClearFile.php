<?php 
class ClearFile
{
	private $_dirname;
	
	public function __construct($swfname)
	{
		error_reporting(E_ALL);
		set_time_limit(0);
		ob_implicit_flush();

		system("title ClearSwf");
		
		$this->_dirname = $swfname;
	}
	
	public function Start()
	{
		$dossier=opendir("decompile/".$this->_dirname);

		while (false != ($file = readdir($dossier))) {
			if($file == '.' || $file == '..')
				continue;
			$extent = explode('.', $file)[1];
			
			if($extent == 'dat')
			{
				$this->ClearDat($file);
			}
			else if($extent == 'png')
			{
				$this->ClearImages($file);
			}
			
		}
	}
	
	private function ClearDat($file)
	{
		$data = file_get_contents("decompile/".$this->_dirname."/".$file);
			
		if(strpos($data,'<?xml') === false)
		{
			unlink("decompile/".$this->_dirname."/".$file);
			return;
		}
		
		$data = $this->RemoveCharBeforeXml($data);
		
		if(strpos($data,'<asset name="sh_') !== false)
			$data = $this->DeleteShSizeAsset($data);
		else if(strpos($data,'<asset name="'.$this->_dirname.'_32_') !== false)
			$data = $this->Delete32SizeAsset($data, $this->_dirname);
		if(strpos($data,'<visualization size="32"') !== false)
			$data = $this->Delete32Size($data);
		if(strpos($data,'<graphics>') === false)
			$data = $this->AddGraphicsTag($data);
			
		file_put_contents("decompile/".$this->_dirname."/".$file, $data);
	}
	
	private function ClearImages($file)
	{
		if(strpos($file, $this->_dirname.'_32_') === false && strpos($file, $this->_dirname.'_sh_') === false)
				return;
			
		unlink("decompile/".$this->_dirname."/".$file);
	}
	
	private function RemoveCharBeforeXml($data)
	{
		return strstr($data, '<?xml');
	}
	
	private function AddGraphicsTag($data)
	{
		$newdata = "";
		$CheckEnd = false;
		$AddTag = false;
		
			foreach (explode('<br />', nl2br($data)) as $line)
			{
				if($CheckEnd && strpos($line,'</visualizationData>') !== false)
				{
					$newdata .= "\n</graphics>";
					$CheckEnd = false;
				}
					
				if($AddTag)
				{
					$newdata .= "\n<graphics>";
					$AddTag = false;
				}
					
				if(strpos($line,'<visualizationData ') !== false)
				{
					$AddTag = true;
					$CheckEnd = true;
				}
				
				$newdata .= $line;
			}
		return $newdata;
	}
	
	private function Delete32Size($data)
	{
		$newdata = "";
		$CheckEnd = false;
		
			foreach (explode('<br />', nl2br($data)) as $line)
			{
				if($CheckEnd && strpos($line,'</visualization>') === false)
					continue;
				else if($CheckEnd && strpos($line,'</visualization>') !== false)
				{
					$CheckEnd = false;
					continue;
				}
					
				
				if(strpos($line,'<visualization size="32"') !== false)
				{
					$CheckEnd = true;
					continue;
				}
				
				if(strpos($line,'_32_') !== false)
					continue;
				$newdata .= $line;
			}
		return $newdata;
	}
	
	private function Delete32SizeAsset($data, $file)
	{
		$newdata = "";
		
			foreach (explode('<br />', nl2br($data)) as $line)
			{
				if(strpos($line,'<asset name="'.$file.'_32_') !== false)
				{
					continue;
				}
				$newdata .= $line;
			}
		return $newdata;
	}
	
	private function DeleteShSizeAsset($data)
	{
		$newdata = "";
		$CheckEnd = false;
		
			foreach (explode('<br />', nl2br($data)) as $line)
			{
				if($CheckEnd && strpos($line,'</asset>') === false)
					continue;
				else if($CheckEnd && strpos($line,'</asset>') !== false)
				{
					$CheckEnd = false;
					continue;
				}
					
				
				if(strpos($line,'<asset name="sh_') !== false)
				{
					$CheckEnd = true;
					continue;
				}
				$newdata .= $line;
			}
		return $newdata;
	}
}


$dossier=opendir("decompile");

while (false != ($file = readdir($dossier))) {
	if($file == '.' || $file == '..')
		continue;
	$namedir = explode('.', $file)[0];
	
	$ClearSwf = new ClearFile($namedir);
	$ClearSwf->Start();
}