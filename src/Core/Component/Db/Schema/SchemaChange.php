<?php
namespace Ububs\Core\Component\Db\Schema;

trait SchemaChange
{

    private static $change_table = 'CHANGE';
    
    public static function change($table, $callback)
    {
        self::getInstance()->setTable($table);
        self::getInstance()->setType(self::$change_table);
        call_user_func($callback, self::getInstance());
        return self::getInstance()->run();
    }
}
