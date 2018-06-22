<?php
namespace Ububs\Core\Component\Db;

interface IDbExcute
{

    public function get();

    public function first();

    public function all();

    public function count();

    public function find(int $id);

    public function value(string $field);

    public function exist();

    public function truncate();

    public function insert(array $data);

    public function create(array $data);

    public function createGetId(array $data);

    public function update(array $data);

    public function delete();

    public function query(string $sql, array $queryData = []);
}
