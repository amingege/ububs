<?php
namespace Ububs\Core\Component\Db\Schema;

trait SchemaCreate
{

    private static $create_type = 'CREATE';
    
    public static function create($table, $callback)
    {
        self::getInstance()->setTable($table);
        self::getInstance()->setType(self::$create_type);
        call_user_func($callback, self::getInstance());
        return self::getInstance()->run();
    }
}
