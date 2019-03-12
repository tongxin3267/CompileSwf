<?php
set_time_limit(0);

class Clear_File
{
    private $_dirname;

    public function __construct($swfname)
    {
        $this->_dirname = $swfname;
    }

    public function start()
    {
        $dossier = opendir("decompile/" . $this->_dirname);

        while (false != ($file = readdir($dossier))) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $extent = explode('.', $file)[1];

            if ($extent == 'dat') {
                $this->_clearDat($file);
            } elseif ($extent == 'png') {
                $this->_clearImages($file);
            }
        }
    }

    private function _clearDat($file)
    {
        $data = file_get_contents("decompile/" . $this->_dirname . "/" . $file);

        if (strpos($data, '<?xml') === false) {
            unlink("decompile/" . $this->_dirname . "/" . $file);
            return;
        }

        $data = $this->_removeCharBeforeXml($data);

        if (strpos($data, '<asset name="sh_') !== false) {
            $data = $this->_deleteShSizeAsset($data);
        } elseif (strpos($data, '<asset name="' . $this->_dirname . '_32_') !== false) {
            $data = $this->_delete32SizeAsset($data, $this->_dirname);
        }

        if (strpos($data, '<visualization size="32"') !== false) {
            $data = $this->_delete32Size($data);
        }

        if (strpos($data, '<graphics>') === false) {
            $data = $this->_addGraphicsTag($data);
        }

        file_put_contents("decompile/" . $this->_dirname . "/" . $file, $data);
    }

    private function _clearImages($file)
    {
        if (strpos($file, $this->_dirname . '_32_') === false && strpos($file, $this->_dirname . '_sh_') === false) {
            return;
        }

        unlink("decompile/" . $this->_dirname . "/" . $file);
    }

    private function _removeCharBeforeXml($data)
    {
        return strstr($data, '<?xml');
    }

    private function _addGraphicsTag($data)
    {
        $newdata = "";
        $CheckEnd = false;
        $AddTag = false;

        foreach (explode('<br />', nl2br($data)) as $line) {
            if ($CheckEnd && strpos($line, '</visualizationData>') !== false) {
                $newdata .= "\n</graphics>";
                $CheckEnd = false;
            }

            if ($AddTag) {
                $newdata .= "\n<graphics>";
                $AddTag = false;
            }

            if (strpos($line, '<visualizationData ') !== false) {
                $AddTag = true;
                $CheckEnd = true;
            }

            $newdata .= $line;
        }
        return $newdata;
    }

    private function _delete32Size($data)
    {
        $newdata = "";
        $CheckEnd = false;

        foreach (explode('<br />', nl2br($data)) as $line) {
            if ($CheckEnd && strpos($line, '</visualization>') === false) {
                continue;
            } elseif ($CheckEnd && strpos($line, '</visualization>') !== false) {
                $CheckEnd = false;
                continue;
            }

            if (strpos($line, '<visualization size="32"') !== false) {
                $CheckEnd = true;
                continue;
            }

            if (strpos($line, '_32_') !== false) {
                continue;
            }

            $newdata .= $line;
        }
        return $newdata;
    }

    private function _delete32SizeAsset($data, $file)
    {
        $newdata = "";

        foreach (explode('<br />', nl2br($data)) as $line) {
            if (strpos($line, '<asset name="' . $file . '_32_') !== false) {
                continue;
            }
            $newdata .= $line;
        }
        return $newdata;
    }

    private function _deleteShSizeAsset($data)
    {
        $newdata = "";
        $CheckEnd = false;

        foreach (explode('<br />', nl2br($data)) as $line) {
            if ($CheckEnd && strpos($line, '</asset>') === false) {
                continue;
            } elseif ($CheckEnd && strpos($line, '</asset>') !== false) {
                $CheckEnd = false;
                continue;
            }

            if (strpos($line, '<asset name="sh_') !== false) {
                $CheckEnd = true;
                continue;
            }
            $newdata .= $line;
        }
        return $newdata;
    }
}

while (false != ($file = readdir(opendir("decompile")))) {
    if ($file == '.' || $file == '..') {
        continue;
    }

    $namedir = explode('.', $file)[0];

    $clearSwf = new Clear_File($namedir);
    $clearSwf->start();
}