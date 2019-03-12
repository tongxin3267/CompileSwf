<?php

class ExtractItem {
	public $decompressed;
	public $io;
	
	public $version;
	public $filelength;
	public $size;
	public $frameRate;
	public $frameCount;
	
	public $tags;
	
	public $files;
	
	public function __construct($data){
		$this->io = new SwfIO($data);
		$this->tags = [];
		$this->files = [
			"images" => [],
			"binaries" => [],
			"symbols" => [],
			"frame" => "",
			"doabc" => []
		];

		$this->GetInfo();
	}
		
	public function GetInfo(){
		$this->io->Decompress();
		$this->version = $this->io->GetUInt8();
		$this->filelength = $this->io->GetUInt32();
		$this->GetSize();
		$this->frameRate = $this->io->collectFixed8();
		$this->frameCount = $this->io->GetUInt16();
		
		
		
		while($this->io->ReamingLength() > 0){
			$tagheader = $this->io->GetUInt16();
			$tagcode = $tagheader >> 6;
			$taglen = $tagheader & 0x3F;
			if($taglen == 0x3F)
				$taglen = $this->io->GetUInt32();
			
			$this->tags[] = [
				"header" => $tagheader,
				"code" => $tagcode,
				"length" => $taglen,
				"offset" => $this->io->Pointer()
			];
			
			$this->io->ResetPointer($this->io->Pointer() + $taglen);
		}
		
		foreach($this->tags as $tag){
			$this->HandleTag($tag);
		}
		//$tag
		
		//var_dump($this, $this->files["binaries"]);
		//echo "<pre>";
		//print_r($this->files);
	}
	
	public function GetSymbolName($id, $def = ""){
		foreach($this->files["symbols"] as $sym)
			if($sym["id"] == $id)
				return $sym["name"];
			
		return $name;
	}
	
	public function HandleTag($tag){
		$tag = (object)$tag;
		$off = $this->io->Pointer();
		$this->io->ResetPointer($tag->offset);
		//$data = $this->io->GetData($tag->offset, $tag->length);
		
		
		switch($tag->code){
			
			//Image [DefineBitsLossless2]
			case 36: 
			case 20:
				$reamingNow = $this->io->Pointer();
				$id = $this->io->GetUInt16();
				$format = $this->io->GetUInt8();
				$w = $this->io->GetUInt16();
				$h = $this->io->GetUInt16();
				
				//if format is 8-bit colormapped image [3]
				if($format == 3)
					$colors = $this->io->GetUInt8();
				
				$zlib = $this->io->GetData($tag->offset + 7, $tag->length - 7);
				$zlibd = gzuncompress($zlib);
				
				$content = "";
				
				
				$image = imagecreatetruecolor($w, $h);
				$rectMask = imageColorAllocateAlpha($image, 255, 0, 255, 127);
				imageFill($image, 0, 0, $rectMask);
				switch($format){
					
					//common format
					case 5:
						$io = new SwfIO($zlibd);
						for($y = 0; $y < $h; $y++)
							for($x = 0; $x < $w; $x++){
								$alpha = $io->GetUInt8();
								$red = $io->GetUInt8();
								$green = $io->GetUInt8();
								$blue = $io->GetUInt8();
								
								$color = imagecolorallocatealpha($image, $red, $green, $blue, 127 - $alpha / 2);
								imagesetpixel($image, $x, $y, $color);
							}
							
							imagesavealpha($image, true);
					break;
					
				}
				
				$content = $image;
				ob_start();
				imagepng($image);
				$content = ob_get_contents();
				ob_end_clean();
				
				
				//imagedestroy($image);
				
				$data = [
					"id" => $id,
					"format" => $format,
					"width" => $w,
					"height" => $h,
					"content" => $content
				];
				
				$this->files["images"][] = $data;
				
			break;
			
			//SymbolClass
			case 76:
				
				$len = $this->io->GetUInt16();
				
				for($i = 0; $i < $len; $i++){
					$symbol = [
						"id" => $this->io->GetUInt16(),
						"name" => $this->io->GetString()
					];
					
					$this->files["symbols"][] = $symbol;
				}
			break;
			
			// Binaries [DefineBinaryData]
			case 87:
				$id = $this->io->GetUInt16();
				$somevalue = $this->io->GetUInt32();
				$data = $this->io->GetData($tag->offset + 6, $tag->length - 6);
				
				$data = [
					"id" => $id,
					"somev" => $somevalue,
					"data" => $data
				];
				$this->files["binaries"][] = $data;
			break;
			
			//FrameLabel
			case 43:
				$name = $this->io->GetString();
				$this->files["frame"] = $name;
			break;
			
			//DoABC2
			case 82:
				$pointernow = $this->io->Pointer();
				$flags = $this->io->GetUInt32();
				$name = $this->io->GetString();
				$diff = $this->io->Pointer() - $pointernow;
				$content = $this->io->GetData($this->io->Pointer(), $tag->length - $diff);
				
				$data = [
					"name" => $name,
					"flags" => $flags,
					"content" => $content
				];
				
				$this->files["doabc"][] = $data;
			break;
		
		}
		
		$this->io->ResetPointer($off);
	}
	
	public function GetSize(){
		$bites = $this->io->collectBits(5);
		$this->size = [
			"minX" => $this->io->collectSB($bites),
			"maxX" => $this->io->collectSB($bites),
			"minY" => $this->io->collectSB($bites),
			"maxY" => $this->io->collectSB($bites),
		];
		$this->io->byteAlign();
		//var_dump([$bites]);
	}
}