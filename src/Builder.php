<?php

namespace DmitrYs\QueryBuilder;


use DmitrYs\QueryBuilder\Common\Enum\DatabaseType;
use DmitrYs\QueryBuilder\Common\Exceptions\RequireParamNotFoundException;
use DmitrYs\QueryBuilder\Drivers\Driver;
use DmitrYs\QueryBuilder\Drivers\MySQL;
use DmitrYs\QueryBuilder\Drivers\PostgreSQL;
use DmitrYs\QueryBuilder\Drivers\SQLite;

class Builder
{
    /**
     * @param DatabaseType $databaseType Тип базы данных
     * @param array $params Массив параметров для подключения
     * @return Driver Экземпляр класса-драйвера для выбранной БД
     * @throws RequireParamNotFoundException
     */
    static public function db(DatabaseType $databaseType, array $params): Driver
    {
        return match ($databaseType) {
            DatabaseType::MySQL => new MySQL($params),
            DatabaseType::PostgreSQL => new PostgreSQL($params),
            DatabaseType::SQLite => new SQLite($params)
        };
    }
}