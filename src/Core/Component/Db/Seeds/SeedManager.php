<?php
namespace Ububs\Core\Component\Db\Seeds;

use Ububs\Core\Component\Factory;

class SeedManager extends Factory
{
    /**
     * 执行 database 目录下某个目录下所有文件
     * @return void
     */
    public function run($dir = null)
    {
        $path = APP_ROOT . 'databases/Seeds';
        if ($dir) {
            $path .= '/' . $dir;
        }
        $files = dir_tree($path);
        if (!empty($files)) {
            foreach ($files as $fDir => $fFiles) {
                array_map(function ($item) use ($fDir) {
                    $rDIr      = str_replace(APP_ROOT . 'databases/', '', $fDir);
                    $tName     = $rDIr . DS . basename($item, '.php');
                    $className = '\Databases\\' . str_replace('/', '\\', $tName);
                    $tObj      = new $className();
                    if (!$tObj instanceof Seed) {
                        throw new \Exception("Error Processing Request", 1);
                    }
                    $tObj->run();
                }, $fFiles);
            }
        }
    }
}
