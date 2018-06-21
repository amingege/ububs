<?php
namespace Ububs\Core\Component\Db\Adapter;

use Ububs\Core\Component\Db\DbQuery;
use Ububs\Core\Component\Db\IDbConnection;
use Ububs\Core\Component\Db\IDbExcute;

class Mysqli extends DbQuery implements IDbConnection, IDbExcute
{

    public function getDbInstance()
    {
        return self::getInstance();
    }

    public function connect()
    {

    }

    public static function getDb()
    {
        if (self::$db === null) {
            $this->connect();
        }
        return self::$db;
    }

    public function destory()
    {

    }

    public function execute(array $params = [])
    {

    }

    public function beginTransaction()
    {

    }

    public function getSql()
    {

    }

    public function commit()
    {

    }

    public function rollback()
    {

    }

    /**
     * 获取列表数据
     * @return array
     */
    public function get()
    {
        list($sql, $queryData) = $this->parseSql(self::SELECT_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
        try {
            $stmt->execute($queryData);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) {
                return $instance->get();
            });
        }
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }

    /**
     * 获取一条数据
     * @return array
     */
    public function first()
    {
        list($sql, $queryData) = $this->parseSql(self::SELECT_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
        try {
            $stmt->execute($queryData);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) {
                return $instance->first();
            });
        }
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $stmt->fetch();
    }

    public function all()
    {

    }

    /**
     * 获取总数
     * @return int
     */
    public function count()
    {
        list($sql) = $this->parseSql(self::COUNT_COMMAND);
        try {
            $stmt = self::getDb()->query($sql);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) {
                return $instance->count();
            });
        }
        return (int) $stmt->fetchColumn();
    }

        /**
     * 判断数据是否存在
     * @return boolean
     */
    public function exist()
    {
        list($sql, $queryData) = $this->parseSql(self::SELECT_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
        try {
            $stmt->execute($queryData);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) {
                return $instance->exist();
            });
        }
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return (bool) $stmt->fetch();
    }

    /**
     * 获取value值
     * @param  string $field 字段
     * @return string
     */
    public function value(string $field)
    {
        $this->selects         = $field;
        list($sql, $queryData) = $this->parseSql(self::SELECT_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
        try {
            $stmt->execute($queryData);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) {
                return $instance->value();
            });
        }
        return $stmt->fetchColumn();
    }

    /**
     * 根据主键获取某一条数据
     * @param  int $id 主键value
     * @return array
     */
    public function find($id)
    {
        // 获取表详情，获取主键
        $tableData    = self::getDb()->query('describe ' . $this->table);
        $searchParams = [
            'id' => $id,
        ];
        foreach ($tableData as $fieldData) {
            if ($fieldData['Key'] == 'PRI') {
                $searchParams = [
                    $fieldData['Field'] => $id,
                ];
                break;
            }
        }
        $this->where($searchParams);
        list($sql, $queryData) = $this->parseSql(self::SELECT_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
        try {
            $stmt->execute($queryData);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) use ($id) {
                return $instance->find($id);
            });
        }
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $stmt->fetch();
    }
}
