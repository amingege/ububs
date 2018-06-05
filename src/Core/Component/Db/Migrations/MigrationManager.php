<?php
namespace Ububs\Core\Component\Db\Migrations;

use Ububs\Core\Component\Factory;

class MigrationManager extends Factory
{

    /**
     * 执行 database 目录下某个目录下所有文件
     * @return void
     */
    public function run($dir = null)
    {
        if ($dir === '' || $dir === null) {
            $files = dir_tree(APP_ROOT . 'databases/Migrations');
            if (!empty($files)) {
                foreach ($files as $fDir => $fFiles) {
                    array_map(function ($item) use ($fDir) {
                        $rDIr = str_replace(APP_ROOT . 'databases/', '', $fDir);
                        $tName   = $rDIr . DS . basename($item, '.php');
                        $className   = '\Databases\\' . str_replace('/', '\\', $tName);
                        $tObj = new $className();
                        if ($tObj instanceof Migration) {
                        	$tObj->run();
                        }
                    }, $fFiles);
                }
            }
        }
    }
}
