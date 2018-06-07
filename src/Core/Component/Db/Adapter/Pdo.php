<?php
namespace Ububs\Core\Component\Db\Adapter;

use Ububs\Core\Component\Factory;

class Pdo extends Factory
{

    private static $db = null;

    private $table   = null;
    private $selects = '*';
    private $updates = [];
    private $wheres  = [];
    private $limit   = [];
    private $orders  = [];

    const COUNT_COMMAND  = 'COUNT';
    const SELECT_COMMAND = 'SELECT';
    const UPDATE_COMMAND = 'UPDATE';
    const DELETE_COMMAND = 'DELETE';
    const INSERT_COMMAND = 'INSERT';

    private static $conditions = ['=', 'like', '<', '>', '<=', '>=', '!=', '<>', 'in', 'not in', 'between', 'not between'];

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
     * 获取数据库资源db对象
     * @return object
     */
    public function getDb()
    {
        if (self::$db === null) {
            $this->connect();
        }
        return self::$db;
    }

    /**
     * 指定 table
     * @param  string $table 表名
     * @return object        dbInstance
     */
    public function table($table)
    {
        $this->resetVar();
        $this->table = $table;
        return self::getInstance();
    }

    /**
     * 清空表数据
     * @return boolean
     */
    public function truncate()
    {
        $sql = "TRUNCATE TABLE " . $this->table;
        return self::getDb()->exec($sql);
    }

    /**
     * 常驻内存多进程下同一个会话请求参数清空
     * @return void
     */
    private function resetVar()
    {
        $this->selects = '*';
        $this->updates = [];
        $this->wheres  = [];
        $this->limit   = [];
        $this->orders  = [];
    }

    /**
     * 选择那些字段
     * @param  array $params 字段
     * @return object         DB对象
     */
    public function selects($params)
    {
        if (is_array($params) && !empty($params)) {
            $this->selects = implode(',', $params);
        }
        return self::getInstance();
    }

    /**
     * where 条件查询
     * 参数说明：
     *     1、数组，key => value，表示相等
     *     2、两个字符串， key = value，表示相等
     *     3、三个字符串，key, condition, value，中间参数为条件
     * @param  [type] $params 查询条件
     * @return object         dbInstance
     */
    public function where(...$params)
    {
        if (empty($params)) {
            return self::getInstance();
        }
        // 数组
        if (count($params) === 1) {
            foreach ($params[0] as $field => $item) {
                if (!is_array($item)) {
                    $this->wheres['='][] = [$field, $item];
                    continue;
                } else {
                    if (count($item) !== 2) {
                        continue;
                    }
                    // key => [condition, value]
                    // key => [condition, []]
                    list($condition, $vs) = $item;
                    $this->wheres[$condition][] = [$field, $vs];
                }
            }
        }
        // 两个字符串
        if (count($params) === 2) {
            list($field, $value) = $params;
            $this->wheres['='][] = [$field, $value];
        }
        if (count($params) === 3) {
            list($field, $condition, $value) = $params;
            if (!is_string($condition) || !in_array($condition, self::$conditions)) {
                throw new \Exception("where\'s condition is error", 1);
            }
            $this->wheres[$condition][] = [$field, $value];
        }
        return self::getInstance();
    }

    /**
     * whereIn 查询
     * @param  array $params 查询条件
     * @return object        DB对象
     */
    public function whereIn(string $field, array $params)
    {
        $this->wheres['in'][] = [$field, $params];
        return self::getInstance();
    }

    /**
     * whereIn 查询
     * @param  array $params 查询条件
     * @return object        DB对象
     */
    public function whereNotIn(string $field, array $params)
    {
        $this->wheres['not in'][] = [$field, $params];
        return self::getInstance();
    }

    /**
     * whereBetween 查询
     * @param  array $params 查询条件
     * @return object        DB对象
     */
    public function whereBetween(string $field, $params)
    {
        $this->wheres['between'][] = [$field, $params];
        return self::getInstance();
    }

    /**
     * whereNotBetween 查询
     * @param  array $params 查询条件
     * @return object        DB对象
     */
    public function whereNotBetween(string $field, $params)
    {
        $this->wheres['not between'][] = [$field, $params];
        return self::getInstance();
    }

    public function orderBy($field, $sort)
    {
        $this->orders[$field] = $sort;
        return self::getInstance();
    }

    /**
     * limit
     * @param  int $start 数值1
     * @param  int $limit 数值2
     * @return object       DB对象
     */
    public function limit(int $start, int $limit = null)
    {
        $this->limit = [$start, $limit];
        return self::getInstance();
    }

    /**
     * 执行原生sql
     * @param  string $sql       sql
     * @param  array  $queryData 查询条件
     * @return array
     */
    public function query($sql, $queryData = [])
    {
        if (empty($queryData)) {
            $stmt = self::getDb()->query($sql, \PDO::FETCH_ASSOC);
            return $stmt->fetchAll();
        }
        $stmt = self::getDb()->prepare($sql);
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
    public function insert($data)
    {
        if (empty($data)) {
            throw new \Exception("Error Processing Request", 1);
        }
        $fileds       = implode(',', array_keys($data[0]));
        $insertValues = trim(str_repeat("(" . trim(str_repeat('?,', count($data[0])), ',') . "),", count($data)), ',');
        $stmt         = self::getDB()->prepare("INSERT INTO {$this->table} ($fileds) VALUES {$insertValues}");
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
    public function create($data)
    {
        if (empty($data)) {
            throw new \Exception("Error Processing Request", 1);
        }
        $fileds = implode(',', array_keys($data));
        $values = ':' . implode(',:', array_keys($data));
        $stmt   = self::getDb()->prepare("INSERT INTO {$this->table} ($fileds) VALUES ({$values})");
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
    public function createGetId($data)
    {
        $fileds = implode(',', array_keys($data));
        $values = ':' . implode(',:', array_keys($data));
        $stmt   = self::getDb()->prepare("INSERT INTO {$this->table} ($fileds) VALUES ({$values})");
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
    public function update($data)
    {
        $this->updates         = $data;
        list($sql, $queryData) = $this->parseSql(self::UPDATE_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
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
        $stmt                  = self::getDb()->prepare($sql);
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
     * 获取 sql
     * @return string
     */
    public function toSql()
    {
        list($sql) = $this->parseSql(self::SELECT_COMMAND);
        return $sql;
    }

    /**
     * leftjoin 联表查询
     * @param  string $table       表名
     * @param  callback $func      回调函数
     * @return object
     */
    public function leftJoin($table, $func)
    {

        \call_user_func($func, self::getInstance());
        return self::getInstance();
    }

    /**
     * on 查询，配合join和leftjoin
     * @param  array $params 查询条件
     * @return object
     */
    public function on($av, $bv)
    {

        return self::getInstance();
    }

    /**
     * 解析sql语句
     * @param  string $type 增删改查类型
     * @return array
     */
    private function parseSql($type)
    {
        if (!$this->table) {
            return errorMessage(500, 'tableName can\'t be eempty');
        }
        $sql       = '';
        $queryData = [];
        switch ($type) {
            case self::COUNT_COMMAND:
                $sql = "SELECT COUNT(*) as count FROM {$this->table}";
                break;

            case self::SELECT_COMMAND:
                $sql = "SELECT {$this->selects} FROM {$this->table}";
                break;

            case self::UPDATE_COMMAND:
                $sql = "UPDATE {$this->table} SET ";
                if (!empty($this->updates)) {
                    foreach ($this->updates as $key => $value) {
                        $sql .= $key . '=:' . $key . ',';
                        $queryData[$key] = $value;
                    }
                    $sql = rtrim($sql, ',');
                }
                break;

            case self::INSERT_COMMAND:
                $sql = "INSERT INTO {$this->table}";
                break;

            case self::DELETE_COMMAND:
                $sql = "DELETE FROM {$this->table}";
                break;

            default:
                # code...
                break;
        }
        // 解析 where 条件
        if ($type !== self::INSERT_COMMAND) {
            $flag = $type === self::COUNT_COMMAND ? false : true;
            $this->parseWhere($sql, $queryData, $flag);
        }
        $this->parseOrder($sql);
        $this->parseLimit($sql);
        return [$sql, $queryData];
    }

    /**
     * 解析 where 条件
     * @param  string $sql       sql语句
     * @param  array $queryData  参数
     * @param  bool  $flag       是否启用预处理
     * @return array
     */
    private function parseWhere(&$sql, &$queryData, $flag = true)
    {
        if (empty($this->wheres)) {
            return true;
        }
        $sql .= " WHERE ";
        $existAnd = false;
        foreach ($this->wheres as $condition => $item) {
            $condition = strtoupper($condition);
            foreach ($item as $index => $data) {
                if ($existAnd) {
                    $sql .= ' AND ';
                }
                $existAnd            = true;
                list($field, $value) = $data;
                // between、notbetween
                if ($condition === 'BETWEEN' || $condition === 'NOT BETWEEN') {
                    $start = isset($value[0]) ? $value[0] : '';
                    $end   = isset($value[1]) ? $value[1] : '';
                    $sql .= " {$field} {$condition} {$start} AND {$end} ";
                    continue;
                }
                // in notin 查询不支持变量绑定
                if (is_array($value)) {
                    $value = implode("','", $value);
                    $sql .= " {$field} {$condition} ('{$value}') ";
                    continue;
                }
                // 关联查询
                if (strpos($field, '.') > -1) {
                    $sql .= " {$field} {$condition} '{$value}' ";
                    continue;
                }
                if ($flag) {
                    $queryData[$field] = $value;
                    $sql .= "{$field} {$condition} :{$field} ";
                } else {
                    $sql .= "{$field} {$condition} '{$value}' ";
                }
            }
        }
        return true;
    }

    /**
     * 解析 order 条件
     * @param  string $sql sql语句
     * @return string
     */
    private function parseOrder(&$sql)
    {
        if (empty($this->orders)) {
            return $sql;
        }
        foreach ($this->orders as $field => $sort) {
            $sql .= " ORDER BY {$field} {$sort} ";
        }
        return $sql;
    }

    /**
     * 解析 limit 条件
     * @param  string $sql sql语句
     * @return string
     */
    private function parseLimit(&$sql)
    {
        if (empty($this->limit)) {
            return true;
        }
        list($start, $limit) = $this->limit;
        $sql .= " LIMIT {$start}";
        if ($limit !== null) {
            $sql .= ", {$limit}";
        }
        return $sql;
    }
}
