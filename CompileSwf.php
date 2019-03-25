<?php
set_time_limit(0);

class Compile_Swf
{
    private $_nameDirCompile;
    private $_newNameFurni;
    private $_dir;
    private $_categoryName;

    private $_allCompil;

    public function __construct($FileName, $all, $CategoryName)
    {
        $this->_nameDirCompile = $FileName;
        $this->_categoryName = $CategoryName;
        $this->_allCompil = $all;
    }

    public function start()
    {
        $this->_pickNewName();
        $this->_initDir();
        $this->_initAs3File();

        exec(__dir__ . '\framework\flex_sdk_4.6\bin\mxmlc.exe "' . __dir__ . '\\' . $this->_dir . '\\' . $this->_newNameFurni . '.as" -static-link-runtime-shared-libraries=true -compiler.strict -swf-version=9');
        copy(__dir__ . '/' . $this->_dir . '/' . $this->_newNameFurni . '.swf', __dir__ . '/swf_compiler/' . $this->_newNameFurni . '.swf');
        $this->_delTree($this->_dir);
    }

    private function _pickNewName()
    {
        if ($this->_allCompil) {
            $this->_newNameFurni = str_replace('-', '_', $this->_nameDirCompile);
        } else {
            echo "\n\r";
            echo "New name swf (or empty for dir name): ";
            $name = fgets(STDIN);
            $name = rtrim($name, "\n\r");

            if (empty($name)) {
                $this->_newNameFurni = $this->_nameDirCompile;
            } else {
                $this->_newNameFurni = $name;
            }
        }
    }

    private function _initDir()
    {
        $compdir = "tmp/" . $this->_newNameFurni;

        if (!is_dir($compdir)) {
            mkdir($compdir);
        } else {
            $this->delTree($compdir);
            mkdir($compdir);
        }

        $this->_dir = $compdir;
    }

    private function _initAs3File()
    {
        $as3 = "";
        $as3source = "";
        $as3base = "";

        $pointeur = opendir("./decompile/" . $this->_categoryName . "/" . $this->_nameDirCompile);
        while (false != ($file = readdir($pointeur))) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $newnamefile = str_replace($this->_nameDirCompile, $this->_newNameFurni, $file);
            $newname = explode('.', $newnamefile)[0];
            $extent = explode('.', $newnamefile)[1];
            if ($extent != 'dat' && $extent != 'png') {
                continue;
            }

            if ($extent == 'dat') {
                $as3source = 'package
			{
				import mx.core.*;
				[Embed(source="' . $newnamefile . '", mimeType="application/octet-stream")]
				public class ' . $newname . ' extends mx.core.ByteArrayAsset
				{
					public function ' . $newname . '()
					{
						super();
						return;
					}
				}
			}
			';
            } elseif ($extent == 'png') {
                $as3source = 'package
			{
				import mx.core.*;
				[Embed(source="' . $newnamefile . '")]
				public class ' . $newname . ' extends mx.core.BitmapAsset
				{
					public function ' . $newname . '()
					{
						super();
						return;
					}
				}
			}
			';
            }

            $dataold = file_get_contents("./decompile/" . $this->_categoryName . "/" . $this->_nameDirCompile . "/" . $file);
            $datanew = str_replace($this->_nameDirCompile, $this->_newNameFurni, $dataold);

            file_put_contents($this->_dir . "/" . $newnamefile, $datanew);
            file_put_contents($this->_dir . "/" . $newname . ".as", $as3source);

            $as3nameclass = explode($this->_newNameFurni . "_", $newname, 2)[1];

            $as3 .= "            public static const " . $as3nameclass . ":Class=" . $newname . ";\n\n";
        }
        closedir($pointeur);

        $as3base = 'package
			{
				import flash.display.*;

				public class ' . $this->_newNameFurni . ' extends flash.display.Sprite
				{
					public function ' . $this->_newNameFurni . '()
					{
						super();
						return;
					}
			        ' . $as3 . '
				}
			}
			';
        file_put_contents($this->_dir . "/" . $this->_newNameFurni . ".as", $as3base);
    }

    private function _delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}

$_choixcompil = [];
$_choixcompil[0] = "AllCompil";

$i = 0;
$openDir = opendir("decompile");
while (false != ($file = readdir($openDir))) {
    if ($file == '.' || $file == '..') {
        continue;
    }

    $i++;
    $_choixcompil[$i] = $file;
}

foreach ($_choixcompil as $key => $value) {
    echo $key . ") " . $value . "\n\r";
}
echo "\n\r";
echo "Enter the number of the file: ";
$choix = fgets(STDIN);
$choix = rtrim($choix, "\n\r");
if (!array_key_exists($choix, $_choixcompil)) {
    exit();
}

echo "\n\r";
echo "You chose: " . $_choixcompil[$choix];

if ($_choixcompil[$choix] == "AllCompil") {
    $dossier = opendir("decompile");
    while (false != ($file = readdir($dossier))) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $compileSwf = new Compile_Swf($file, true, "");
        $compileSwf->Start();
    }
} else {
    $file = $_choixcompil[$choix];

    $compileSwf = new Compile_Swf($file, false, "");
    $compileSwf->Start();
}
