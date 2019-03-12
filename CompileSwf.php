<?php
set_time_limit(0);

class CompileSwf
{
    private $_NameDirCompile;
    private $_NewNameFurni;
    private $_Dir;
    private $_categoryName;

    private $_AllCompil;

    public function __construct($FileName, $all, $CategoryName)
    {

        system("title Compile SWF");

        $this->_NameDirCompile = $FileName;
        $this->_categoryName = $CategoryName;
        $this->_AllCompil = $all;
    }

    public function Start()
    {
        $this->PickNewName();
        $this->InitDir();
        $this->InitAs3File();

        exec(__DIR__ . '\framework\flex_sdk_4.6\bin\mxmlc.exe "' . __DIR__ . '\\' . $this->_Dir . '\\' . $this->_NewNameFurni . '.as" -static-link-runtime-shared-libraries=true -compiler.strict -swf-version=9');
        copy(__DIR__ . '/' . $this->_Dir . '/' . $this->_NewNameFurni . '.swf', __DIR__ . '/swf_compiler/' . $this->_NewNameFurni . '.swf');
        $this->delTree($this->_Dir);
    }

    private function PickNewName()
    {
        if ($this->_AllCompil) {
            $this->_NewNameFurni = str_replace('-', '_', $this->_NameDirCompile);
        } else {
            echo "\n\r";
            echo "New name swf (or empty for dir name): ";
            $name = fgets(STDIN);
            $name = rtrim($name, "\n\r");

            if (empty($name)) {
                $this->_NewNameFurni = $this->_NameDirCompile;
            } else {
                $this->_NewNameFurni = $name;
            }

        }
    }

    private function InitDir()
    {
        $compdir = "tmp/" . $this->_NewNameFurni;

        if (!is_dir($compdir)) {
            mkdir($compdir);
        } else {
            $this->delTree($compdir);
            mkdir($compdir);
        }

        $this->_Dir = $compdir;
    }

    private function InitAs3File()
    {
        $as3 = "";
        $as3source = "";
        $as3base = "";

        $pointeur = opendir("./decompile/" . $this->_categoryName . "/" . $this->_NameDirCompile);
        while (false != ($file = readdir($pointeur))) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $newnamefile = str_replace($this->_NameDirCompile, $this->_NewNameFurni, $file);
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

            $dataold = file_get_contents("./decompile/" . $this->_categoryName . "/" . $this->_NameDirCompile . "/" . $file);
            $datanew = str_replace($this->_NameDirCompile, $this->_NewNameFurni, $dataold);

            file_put_contents($this->_Dir . "/" . $newnamefile, $datanew);
            file_put_contents($this->_Dir . "/" . $newname . ".as", $as3source);

            $as3nameclass = explode($this->_NewNameFurni . "_", $newname, 2)[1];

            $as3 .= "            public static const " . $as3nameclass . ":Class=" . $newname . ";\n\n";
        }
        closedir($pointeur);

        $as3base = 'package
			{
				import flash.display.*;

				public class ' . $this->_NewNameFurni . ' extends flash.display.Sprite
				{
					public function ' . $this->_NewNameFurni . '()
					{
						super();
						return;
					}
			        ' . $as3 . '
				}
			}
			';
        file_put_contents($this->_Dir . "/" . $this->_NewNameFurni . ".as", $as3base);

    }

    private function delTree($dir)
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
$pointeur = opendir("decompile");
while (false != ($file = readdir($pointeur))) {
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

        $CompileSwf = new CompileSwf($file, true, "");
        $CompileSwf->Start();
    }
} else {
    $file = $_choixcompil[$choix];

    $CompileSwf = new CompileSwf($file, false, "");
    $CompileSwf->Start();
}