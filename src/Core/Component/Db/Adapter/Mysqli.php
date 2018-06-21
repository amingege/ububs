<?php
namespace Ububs\Core\Component\Db\Adapter;

use Ububs\Core\Component\Db\DbQuery;
use Ububs\Core\Component\Db\IDbConnection;
use Ububs\Core\Component\Factory;

class Mysqli extends Factory implements IDbConnection
{

    use DbQuery;

    public function connect()
    {

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
}
