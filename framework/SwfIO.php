<?php
class SwfIO {
	
	public $raw;
	public $bytePointer;
	public $bitPointer;
	
	public function __construct($data){
		$this->bytePointer = 0;
		$this->bitPointer = 0;
		$this->raw = $data;
	}
	
	public function Decompress(){
		
		$sign = $this->GetBytes(3);
		if($sign == "CWS"){
			$this->raw = substr($this->raw, 0, 8) . gzuncompress(substr($this->raw, 8));
		}elseif($sign == "FWS"){
			//nothing
		}
	}
	
	public function ReamingLength(){
		return $this->Length() - $this->bytePointer;
	}
	
	public function Length(){
		return strlen($this->raw);
	}
	
	public function ResetPointer($point = 0){
		$this->bytePointer = $point;
	}
	
	public function Pointer(){
		return $this->bytePointer;
	}	
	
	public function GetData($offset, $length = -1){
		if($length > 0)
			return substr($this->raw, $offset, $length);
		
		return substr($this->raw, $offset);
	}
	
	// Bytes Handling
	public function GetBytes($amount){
		$data = substr($this->raw, $this->bytePointer, $amount);
		$this->bytePointer += $amount;
		return $data;
	}
	
	public function GetUInt8(){
		return ord($this->GetBytes(1));
	}
	
	public function GetUInt16(){
		$val = 0;
		$val += ord($this->GetBytes(1));
		$val += ord($this->GetBytes(1)) << 8;
		
		return $val;
	}
	
	public function collectFixed8() {
	$lo = $this->GetUInt8();
	$hi = $this->GetUInt8();
	if ($hi < 128) {
	    $ret = $hi + $lo / 256.0;
	} else {
	    $full = 65536 - (256 * $hi + $lo);
	    $hi = $full >> 8;
	    $lo = $full & 0xff;
	    $ret = -($hi + $lo / 256.0);
	}
	return $ret;
    }
	
	public function GetUInt32(){
		$ns = [
			$this->GetUInt8(),
			$this->GetUInt8(),
			$this->GetUInt8(),
			$this->GetUInt8()
		];
		
		return $ns[3] << 24 | $ns[2] << 16 | $ns[1] << 8 | $ns[0];
	}
	
	public function GetString(){
		$point = $this->bytePointer;
		while($this->GetUInt8() != 0){
			;
		}
		
		return substr($this->raw, $point, $this->bytePointer - 1 - $point);
	}
	
	//Bites Hadling
	
	
	
	public function GetBites($amount){
		$pointer = $this->bytePointer;
		$data = $this->GetUInt32();
		
		$result = (($data << $this->bitPointer) & 0xffffffff) >> (32 - $amount);
	
		return $result;
	}
	
	public function collectBits($num) {
		$value = 0;
		while ($num > 0) {
			$nextbits = ord($this->raw[$this->bytePointer]);
			$bitsFromHere = 8 - $this->bitPointer;
			if ($bitsFromHere > $num) {
			$bitsFromHere = $num;
			}
			$value |= (($nextbits >> (8 - $this->bitPointer - $bitsFromHere)) &
				   (0xff >> (8 - $bitsFromHere))) << ($num - $bitsFromHere);
			$num -= $bitsFromHere;
			$this->bitPointer += $bitsFromHere;
			if ($this->bitPointer >= 8) {
			$this->bitPointer = 0;
			$this->bytePointer++;
			}
		}
		return $value;
	}
	
	public function collectSB($num) {
	$val = $this->collectBits($num);
	if($val == 0)
		return 0;
	if ($val >= (1 << ($num - 1))) { // If high bit is set
	    $val -= 1 << $num; // Negate
	}
	return $val;
    }
	
	public function byteAlign() {
		if ($this->bitPointer != 0) {
			$this->bytePointer++;
			$this->bitPointer = 0;
		}
    }
	
}