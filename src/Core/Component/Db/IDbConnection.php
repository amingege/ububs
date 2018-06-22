<?php
namespace Ububs\Core\Component\Db;

interface IDbConnection
{

	public function getDbInstance();

    public function connect();

    public function destory();

    public function beginTransaction();

    public function commit();

    public function rollback();
}
