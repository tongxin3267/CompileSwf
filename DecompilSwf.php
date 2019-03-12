<?php
set_time_limit(0);

require 'framework/SwfIO.php';
require 'framework/ExtractItem.php';

class Decompil_Swf
{
    const DIRCOMP = 'swf_decompiler/';
    const DIRDECOMP = 'decompile/';

    private $_dirSwf;
    private $_swfName;

    private $_swf;

    public function __construct($swfname)
    {
        $this->_dirSwf = self::DIRDECOMP . $swfname . "/";
        $this->_swfName = $swfname;
    }

    public function start()
    {
        $this->_initSWF();
        $this->_extractFiles();
    }

    private function _initSWF()
    {
        $dirswf = $this->_dirSwf;
        if (!is_dir($dirswf)) {
            mkdir($dirswf);
        }

        $data = file_get_contents(self::DIRCOMP . $this->_swfName . ".swf");
        $this->_swf = new ExtractItem($data);
    }

    private function _extractFiles()
    {
        $swf = $this->_swf;
        foreach ($swf->files["binaries"] as $bin) {
            $binname = $swf->GetSymbolName($bin["id"]);
            $data = $bin["data"];

            file_put_contents($this->_dirSwf . $binname . ".dat", $data);
        }

        foreach ($swf->files["images"] as $img) {
            $imgname = $swf->GetSymbolName($img["id"]);
            $data = $img["content"];

            file_put_contents($this->_dirSwf . $imgname . ".png", $data);
        }
    }
}

while (false != ($file = readdir(opendir("swf_decompiler")))) {
    if ($file == '.' || $file == '..') {
        continue;
    }
    $nameswf = explode('.', $file)[0];

    try {
        $decompilSwf = new Decompil_Swf($nameswf);
        $decompilSwf->start();
    } catch (Exception $e) {
        echo $e . "\n\r";
    }

    echo "Decompile " . $file . "\n\r";
}

echo "Complete";