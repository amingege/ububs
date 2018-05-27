<?php
namespace Ububs\Core\Component\Db;

use FwSwoole\Core\Tool\Config;
use Ububs\Core\Component\Db\Adapter\Pdo;
use Ububs\Core\Component\Factory;

class Db extends Factory
{

    private static $dbInstance = null;

    const TASK_TYPE = 'DB';

    const PDO_SERVER    = 'PDO';
    const MYSQLI_SERVER = 'MYSQLI';

    private static function getDbInstance()
    {
        if (self::$dbInstance === null) {
            switch (strtoupper(config('database.type'))) {
                case self::PDO_SERVER:
                    if (!\extension_loaded('pdo')) {
                        throw new \Exception("no pdo extension. get: https://github.com/swoole/swoole-src");
                    }
                    self::$dbInstance = Pdo::getInstance();
                    break;

                case self::MYSQLI_SERVER:
                    # code...
                    break;

                default:
                    # code...
                    break;
            }
        }
        return self::$dbInstance;
    }

    public static function table($table)
    {
        return self::getDbInstance()->table($table);
    }

    public static function query($sql, $queryData = [])
    {
        return self::getDbInstance()->query($sql, $queryData);
    }

    public static function taskQuery($sql, $queryData = [])
    {
        return getServ()->taskwait([
            '__TASK_TYPE__' => self::TASK_TYPE,
            'data'          => json_encode([$sql, $queryData]),
        ]);
    }
}
