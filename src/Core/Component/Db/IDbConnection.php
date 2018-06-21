<?php
namespace Ububs\Core\Component\Db;

interface IDbConnection
{

	public function getDb();

    public function connect();

    public function destory();

    public function execute(array $params = []);

    public function beginTransaction();

    public function getSql();

    public function commit();

    public function rollback();
}
