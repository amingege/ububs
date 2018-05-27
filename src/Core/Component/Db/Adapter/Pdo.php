<?php
namespace Ububs\Core\Component\Db\Adapter;

use Ububs\Core\Component\Factory;

class Pdo extends Factory
{

    private static $db = null;

    public function connect()
    {
        $config = config('database');
        try {
            self::$db = new \PDO(
                "mysql:host=" . $config['host'] . ";port=" . $config['port'] . ";dbname=" . $config['databaseName'] . "",
                $config['user'],
                $config['password'], array(
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '" . $config['charset'] . "';",
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_PERSISTENT         => true,
                ));
            return self::$db;
        } catch (PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getDb()
    {
        if (self::$db === null) {
            $this->connect();
        }
        return self::$db;
    }

    public function table($table)
    {

    }

    public function query($sql, $queryData = [])
    {
        if (empty($queryData)) {
            $stmt = self::getDb()->query($sql);
            return $stmt->fetchAll();
        }
        $stmt = self::getDb()->prepare($sql);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        $stmt->execute($queryData);
        return $stmt->fetchAll();
    }
}
