<?php
namespace Ububs\Core\Component\Db;

use FwSwoole\Core\Tool\Config;
use Ububs\Core\Component\Db\Adapter\Mysqli;
use Ububs\Core\Component\Db\Adapter\Pdo;
use Ububs\Core\Component\Factory;

class Db extends Factory
{

    private static $dbInstance = null;

    const TASK_TYPE = 'DB';

    const PDO_SERVER    = 'PDO';
    const MYSQLI_SERVER = 'MYSQLI';

    /**
     * 获取数据库资源对象
     * @return object dbInstance
     */
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
                    if (!\extension_loaded('mysqli')) {
                        throw new \Exception("no mysqli extension. get: https://github.com/swoole/swoole-src");
                    }
                    self::$dbInstance = Mysqli::getInstance();
                    break;

                default:
                    # code...
                    break;
            }
        }
        return self::$dbInstance;
    }

    /**
     * 指定tablename
     * @param  string $table 表名
     * @return object        dbInstance
     */
    public static function table($table)
    {
        if (!$table) {
            return errorMessage('tableName can\'t be eempty');
        }
        return self::getDbInstance()->table($table);
    }

    /**
     * 执行原生sql命令
     * @param  string $sql       sql
     * @param  array  $queryData 参数
     * @return array
     */
    public static function query($sql, $queryData = [])
    {
        return self::getDbInstance()->query($sql, $queryData);
    }

    /**
     * mysql连接池调用sql
     * @param  string $sql       sql
     * @param  array  $queryData 参数
     * @return object
     */
    public static function taskQuery($sql, $queryData = [])
    {
        return getServ()->taskwait([
            '__TASK_TYPE__' => self::TASK_TYPE,
            'data'          => json_encode([$sql, $queryData]),
        ]);
    }
}
