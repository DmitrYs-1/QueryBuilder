<?php

namespace DmitrYs\QueryBuilder\Drivers;

use DmitrYs\QueryBuilder\Common\Exceptions\QuerySequenceBrokenExceptions;
use DmitrYs\QueryBuilder\Common\Exceptions\RequireParamNotFoundException;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;

class MySQL extends Driver
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
     * @throws mysqli_sql_exception
     */
    protected function connect(): void
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        @$this->db = new mysqli($this->params['host'], $this->params['user'], $this->params['password'], $this->params['database'], $this->params['port']);
        $this->db->set_charset($this->params['charset'] ?? 'UTF8');
    }

    /**
     * @return mysqli_result|true Объект результата или true для запросов без результата
     * @throws QuerySequenceBrokenExceptions|mysqli_sql_exception
     */
    public function execute():mysqli_result|true
    {
        parent::execute();
        if(!$res = $this->db->query($this->preparedQuery)){
            throw new mysqli_sql_exception("Ошибка выполнения SQL запроса");
        }
        return $res;
    }

    /**
     * @throws QuerySequenceBrokenExceptions|mysqli_sql_exception
     */
    public function getAll(): array
    {
        $result = $this->execute();
        $data = [];
        while ($res = mysqli_fetch_assoc($result)){
            $data[] = $res;
        }
        return $data;
    }

    /**
     * @throws QuerySequenceBrokenExceptions|mysqli_sql_exception
     */
    public function getRow(): array
    {
        return mysqli_fetch_assoc($this->execute());
    }

    /**
     * @throws QuerySequenceBrokenExceptions|mysqli_sql_exception
     */
    public function getValue():mixed
    {
        return mysqli_fetch_column($this->execute());
    }
}