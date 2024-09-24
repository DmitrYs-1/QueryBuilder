<?php

namespace DmitrYs\QueryBuilder\Drivers;

use DmitrYs\QueryBuilder\Common\Exceptions\QuerySequenceBrokenExceptions;
use DmitrYs\QueryBuilder\Common\Exceptions\RequireParamNotFoundException;
use SQLite3;
use SQLite3Result;

class SQLite extends Driver
{
    /**
     * @throws RequireParamNotFoundException
     */
    public function __construct(array $params)
    {
        if(empty($params['path'])){
            throw new RequireParamNotFoundException("Не заданы обязательные параметры");
        }
        parent::__construct($params);
    }

    public function execute():SQLite3Result|null
    {
        parent::execute();
        return $this->db->query($this->preparedQuery);
    }

    protected function connect(): void
    {
        $this->db = new SQLite3($this->params['path']);
    }

    /**
     * @throws QuerySequenceBrokenExceptions
     */
    public function getAll(): array
    {
        $result = $this->execute();
        $data = [];
        while($res = $result->fetchArray(SQLITE3_ASSOC)){
            $data[] = $res;
        }
        return $data;
    }

    /**
     * @throws QuerySequenceBrokenExceptions
     */
    public function getRow(): array
    {
        return $this->execute()->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * @throws QuerySequenceBrokenExceptions
     */
    public function getValue(): mixed
    {
        return $this->execute()->fetchArray()[0];
    }
}