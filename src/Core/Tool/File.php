<?php
namespace Ububs\Core\Tool;

class File
{
    public function isExist($filePath)
    {
        $filePath = str_replace('\\', '/', $filePath);
        return (bool) is_file($filePath);
    }

    public function del()
    {

    }

    public function make()
    {

    }


}