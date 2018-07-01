<?php
namespace Ububs\Core\Component\Db\Adapter;

use Ububs\Core\Component\Db\DbQuery;
use Ububs\Core\Component\Db\IDbConnection;
use Ububs\Core\Component\Db\IDbExcute;

class Pdo extends DbQuery implements IDbConnection, IDbExcute
{

    public function getDbInstance()
    {
        return self::getInstance();
    }

    public function destory()
    {

    }

    public function beginTransaction()
    {

    }

    public function commit()
    {

    }

    public function rollback()
    {

    }

    /**
     * 连接数据库
     * @return objcet
     */
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

    public function resetConnect($msg, $callback)
    {
        if (strpos($msg, 'MySQL server has gone away') !== false) {
            self::$db = null;
            return call_user_func($callback, self::getInstance());
        } else {
            throw new \Exception($msg, 1);
        }
    }

    /**
     * 清空表数据
     * @return boolean
     */
    public function truncate()
    {
        $sql = "TRUNCATE TABLE " . $this->table;
        return $this->getDb()->exec($sql);
    }

    /**
     * 执行原生sql
     * @param  string $sql       sql
     * @param  array  $queryData 查询条件
     * @return array
     */
    public function query(string $sql, array $queryData = [])
    {
        if (empty($queryData)) {
            $stmt = $this->getDb()->query($sql, \PDO::FETCH_ASSOC);
            return $stmt->fetchAll();
        }
        $stmt = $this->getDb()->prepare($sql);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        try {
            $stmt->execute($queryData);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) use ($sql, $queryData) {
                return $instance->query($sql, $queryData);
            });
        }
        return $stmt->fetchAll();
    }

    /**
     * 插入多条数据
     * @param  array $data
     * @return bool
     */
    public function insert(array $data)
    {
        if (empty($data)) {
            throw new \Exception("Error Processing Request", 1);
        }
        $fileds       = implode(',', array_keys($data[0]));
        $insertValues = trim(str_repeat("(" . trim(str_repeat('?,', count($data[0])), ',') . "),", count($data)), ',');
        $stmt         = $this->getDb()->prepare("INSERT INTO {$this->table} ($fileds) VALUES {$insertValues}");
        $queryData    = [];
        foreach ($data as $key => $item) {
            $queryData = array_merge($queryData, array_values($item));
        }
        try {
            return $stmt->execute($queryData);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) use ($data) {
                return $instance->insert($data);
            });
        }
    }

    /**
     * 新增一条数据
     * @param  array $data
     * @return bool       是否新增成功
     */
    public function create(array $data)
    {
        if (empty($data)) {
            throw new \Exception("Error Processing Request", 1);
        }
        $fileds = implode(',', array_keys($data));
        $values = ':' . implode(',:', array_keys($data));
        $stmt   = $this->getDb()->prepare("INSERT INTO {$this->table} ($fileds) VALUES ({$values})");
        try {
            return $stmt->execute($data);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) use ($data) {
                return $instance->create($data);
            });
        }
    }

    /**
     * 新增一条数据，返回插入的自增主键
     * @param  array $data
     * @return int
     */
    public function createGetId(array $data)
    {
        $fileds = implode(',', array_keys($data));
        $values = ':' . implode(',:', array_keys($data));
        $stmt   = $this->getDb()->prepare("INSERT INTO {$this->table} ($fileds) VALUES ({$values})");
        try {
            $stmt->execute($data);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) use ($data) {
                return $instance->createGetId($data);
            });
        }
        return self::$db->lastInsertId();
    }

    /**
     * 更新
     * @param  array  更新数据
     * @return bool   是否更新成功
     */
    public function update(array $data)
    {
        $this->updates         = $data;
        list($sql, $queryData) = $this->parseSql(self::UPDATE_COMMAND);
        $stmt                  = $this->getDb()->prepare($sql);
        try {
            return $stmt->execute($queryData);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) use ($data) {
                return $instance->update($data);
            });
        }
    }

    /**
     * 删除
     * @return bool 是否删除成功
     */
    public function delete()
    {
        list($sql, $queryData) = $this->parseSql(self::DELETE_COMMAND);
        $stmt                  = $this->getDb()->prepare($sql);
        try {
            return $stmt->execute($queryData);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) {
                return $instance->delete();
            });
        }
    }

    /**
     * 获取总数
     * @return int
     */
    public function count()
    {
        list($sql) = $this->parseSql(self::COUNT_COMMAND);
        try {
            $stmt = $this->getDb()->query($sql);
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
        $stmt                  = $this->getDb()->prepare($sql);
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
        $stmt                  = $this->getDb()->prepare($sql);
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
    public function find(int $id)
    {
        // 获取表详情，获取主键
        $tableData    = $this->getDb()->query('describe ' . $this->table);
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
        $stmt                  = $this->getDb()->prepare($sql);
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

    /**
     * 获取一条数据
     * @return array
     */
    public function first()
    {
        list($sql, $queryData) = $this->parseSql(self::SELECT_COMMAND);
        $stmt                  = $this->getDb()->prepare($sql);
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

    /**
     * 获取列表数据
     * @return array
     */
    public function get()
    {
        list($sql, $queryData) = $this->parseSql(self::SELECT_COMMAND);
        $stmt                  = $this->getDb()->prepare($sql);
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

    public function all()
    {

    }

    public function min(string $field)
    {
        $this->minField = $field;
        list($sql, $queryData) = $this->parseSql(self::SELECT_MIN);
        $stmt                  = $this->getDb()->prepare($sql);
        try {
            $stmt->execute($queryData);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) {
                return $instance->first();
            });
        }
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $stmt->fetch()[$this->minField];
    }

    public function max(string $field)
    {
        $this->maxField = $field;
        list($sql, $queryData) = $this->parseSql(self::SELECT_MAX);
        $stmt                  = $this->getDb()->prepare($sql);
        try {
            $stmt->execute($queryData);
        } catch (\PDOException $e) {
            return $this->resetConnect($e->getMessage(), function ($instance) {
                return $instance->first();
            });
        }
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $stmt->fetch()[$this->maxField];
    }
}
