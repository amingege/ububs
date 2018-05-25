<?php
namespace Ububs\Core\Tool\File;

use Ububs\Core\Tool\Factory;

class File extends Factory
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