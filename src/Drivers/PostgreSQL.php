<?php

namespace DmitrYs\QueryBuilder\Drivers;

use DmitrYs\QueryBuilder\Common\Exceptions\QuerySequenceBrokenExceptions;
use DmitrYs\QueryBuilder\Common\Exceptions\RequireParamNotFoundException;
use Exception;
use PgSql\Result;

class PostgreSQL extends Driver
{

    /**
     * @throws RequireParamNotFoundException
     */
    public function __construct(array $params)
    {
        if(empty($params['host']) || empty($params['port']) || empty($params['user']) || empty($params['password']) || empty($params['database'])){
            throw new RequireParamNotFoundException("Не заданы обязательные параметры");
        }
        parent::__construct($params);
    }

    /**
     * @throws Exception
     */
    protected function connect(): void
    {
        if(!$res = @pg_connect("host={$this->params['host']} port={$this->params['port']} dbname={$this->params['database']} user={$this->params['user']} password={$this->params['password']}")){
            throw new Exception("Не удалось подключиться. Проверьте правильность данных");
        }
        $this->db = $res;
    }

    /**
     * @throws QuerySequenceBrokenExceptions
     * @throws Exception
     */
    public function execute(): Result
    {
        parent::execute();
        $this->preparedQuery = str_replace('`', '"', $this->preparedQuery);
        if(!$res = pg_query($this->db, $this->preparedQuery)){
            throw new Exception("Ошибка выполнения запроса: ".pg_last_error($this->db));
        }
        return $res;
    }

    /**
     * @throws QuerySequenceBrokenExceptions
     */
    public function getAll(): array
    {
        $result = $this->execute();
        $data = [];
        while($res = pg_fetch_assoc($result)){
            $data[] = $res;
        }
        return $data;
    }


    /**
     * @throws QuerySequenceBrokenExceptions
     * @throws Exception
     */
    public function getRow(): array
    {
        $res = $this->execute();
        return pg_fetch_row($res);
    }

    /**
     * @throws QuerySequenceBrokenExceptions|Exception
     */
    public function getValue(): false|string|null
    {
        $res = $this->execute();
        return pg_fetch_result($res, 0);
    }
}