<?php

namespace DmitrYs\QueryBuilder\Drivers;

use DmitrYs\QueryBuilder\Common\Enum\OrderByDirection;
use DmitrYs\QueryBuilder\Common\Enum\QueryType;
use DmitrYs\QueryBuilder\Common\Enum\WhereType;
use DmitrYs\QueryBuilder\Common\Exceptions\QuerySequenceBrokenExceptions;
use DmitrYs\QueryBuilder\Common\Exceptions\RequireParamNotFoundException;
use mysqli_sql_exception;

abstract class Driver
{
    /**
     * @var array Массив параметров для подключения к БД
     */
    protected array $params;

    /**
     * @var QueryType Тип текущего запроса
     */
    protected QueryType $type;

    /**
     * @var string Первая часть запроса
     */
    protected string $query;

    /**
     * @var array Часть запроса, отвечающая за сортировку
     */
    protected array $orderBy;

    /**
     * @var mixed Объект базы данных
     */
    protected mixed $db;

    /**
     * @var string Готовый к выполнению запрос
     */
    protected string $preparedQuery;

    /**
     * @var array Части условий запроса
     */
    protected array $where;

    /**
     * @var int Лимит строк запроса
     */
    private int $limit = 0;

    /**
     * Конструктор
     * Принимает массив параметров для подключения, проверяет на наличие необходимых значений
     * @param array $params {MySQL/PostgreSQL: host: Хост БД, port: Порт БД, user: Пользователь БД, pass: Пароль БД, database: Название БД}
     * {SQLite: path: путь к файлу БД}
     * @throws mysqli_sql_exception
     */
    function __construct(array $params){
        $this->params = $params;
        $this->connect();
    }


    /**
     * Получить подготовленную строку запроса
     * Можно использовать для дебага
     * @api
     * @throws QuerySequenceBrokenExceptions
     */
    public function getPreparedQuery(): string
    {
        if(empty($this->preparedQuery)){
            $this->prepareQuery();
        }
        return $this->preparedQuery;
    }


    /**
     * Подключение к БД
     *
     * @return void
     */
    abstract protected function connect(): void;

    /**
     * Выполнение запроса к БД
     * @return mixed Объект результата запроса
     * @throws QuerySequenceBrokenExceptions
     */
    public function execute(): mixed
    {
        if(in_array($this->type, [QueryType::Select, QueryType::Update, QueryType::Delete])){
            $this->prepareQuery();
        }
        if(empty($this->preparedQuery)){
            throw new QuerySequenceBrokenExceptions("Нет подготовленного запроса для выполнения");
        }
        return 0;
    }

    /**
    * Получить массив содержащий все строки результата запроса
    * @return array
    */
    abstract public function getAll(): array;

    /**
     * Получить массив содержащий первую строку запроса
     * @return array
     */
    abstract public function getRow(): array;

    /**
     * Получить один элемент из результата запроса
     * @return mixed
     */
    abstract public function getValue():mixed;

    /**
     * Сбрасывает состояние объекта, за исключением $this->db и $this->param
     * @return void
     */
    protected function reset():void
    {
        foreach (get_class_vars(__CLASS__) as $name => $var){
            if(in_array($name, ['params', 'db'])){
                continue;
            }
            unset($this->$name);
        }
        $this->limit = 0;
    }

    /**
     * Начало запроса SELECT
     *
     * Устанавливает значение $this->type = QueryType::Select
     * Добавляет строку "SELECT {$columns}" в $this->query
     *
     * @api
     * @example $db->select(['col1', 'col2'])
     * @param array $columns
     * @return $this
     */
    public function select(string $table, array $columns): Driver
    {
        $this->reset();
        $this->type = QueryType::Select;

        $columns = join(',', $columns);
        $this->query = "SELECT `$columns` FROM `$table` ";
        return $this;
    }

    /**
     * Добавляет условия в $this->where
     *
     * Может вызываться несколько раз для одного запроса
     *
     * @api
     * @param string $column Название колонки
     * @param string $compare Знак сравнения
     * @param mixed $value Значение для проверки
     * @param WhereType|null $type Задаётся для второго и последующих условий. Устанавливает OR или AND
     * @return $this
     * @throws QuerySequenceBrokenExceptions
     * @example $db->select(['col1', 'col2'])->from('table')->where('col1', '=', 'a')->where(('col2', '<', 10, WhereType::AND))
     */
    public function where(string $column, string $compare, mixed $value, WhereType|null $type = null): Driver
    {
        if(empty($this->query) || !in_array($this->type, [QueryType::Select, QueryType::Update, QueryType::Delete])){
            throw new QuerySequenceBrokenExceptions("Нарушена последовательность. Сначала необходимо вызвать \"from\", \"update\" или \"delete\"");
        }

        if(empty($this->where)){
            $type = 0;
        }

        $this->where[][$type->value ?? 0] = [$column, $compare, $value];
        return $this;
    }

    /**
     * Добавляет сортировку в $this->orderBy
     *
     * Может вызываться несколько раз для одного запроса
     *
     * @api
     * @param string $column Столбец для сортировки
     * @param OrderByDirection $direction Направление сортировки. Не обязательно. По умолчанию ASC
     * @return $this
     * @throws QuerySequenceBrokenExceptions
     * @example $db->select(['col1', 'col2'])->from('table')->where('col1', '=', 'a')->where(('col2', '<', 10, WhereType::AND))->orderBy('col1');
     */
    public function orderBy(string $column, OrderByDirection $direction = OrderByDirection::ASC): Driver
    {
        if(empty($this->query) || $this->type !== QueryType::Select){
            throw new QuerySequenceBrokenExceptions("Нарушена последовательность. Сначала необходимо вызвать \"from\"");
        }
        $this->orderBy[] = "$column $direction->name";
        return $this;
    }

    /**
     * Добавляет параметр LIMIT в запрос
     * @api
     * @param int $limit
     * @return void
     * @throws QuerySequenceBrokenExceptions
     */
    public function limit(int $limit): void
    {
        if(empty($this->query) || $this->type !== QueryType::Select){
            throw new QuerySequenceBrokenExceptions("Нарушена последовательность. Сначала необходимо вызвать \"select\"");
        }
        $this->limit = $limit;
    }

    /**
     * Формирует запрос INSERT
     * @api
     * @param string $table Название таблицы для вставки
     * @param array $data Массив данных ['column'=>'value']
     * @throws RequireParamNotFoundException
     */
    public function insert(string $table, array $data):Driver
    {
        if(empty($data)){
            throw new RequireParamNotFoundException("Массив с данными не может быть пустым");
        }else if(empty($table)) {
            throw new RequireParamNotFoundException("Не указано название таблицы");
        }

        $this->reset();
        $this->type = QueryType::Insert;

        $columns = [];
        $values = [];
        foreach ($data as $column => $value){
            $columns[] = $column;
            $values[] = $value;
        }
        $columns = join(', ', $columns);
        $values = join(', ', array_map(function($string) {
            return '\'' . $string . '\'';
        }, $values));
        $this->preparedQuery = "INSERT INTO `$table` ($columns) VALUES ($values)";
        return $this;
    }

    /**
     * Формирует запрос UPDATE
     *
     * Далее обязательно нужно вызвать where
     *
     * @api
     * @param string $table Название таблицы для вставки
     * @param array $data Массив данных ['column'=>'value']
     * @throws RequireParamNotFoundException
     */
    public function update(string $table, array $data):Driver
    {
        if(empty($data)){
            throw new RequireParamNotFoundException("Массив с данными не может быть пустым");
        }else if(empty($table)) {
            throw new RequireParamNotFoundException("Не указано название таблицы");
        }

        $this->reset();
        $this->type = QueryType::Update;

        $set = [];
        foreach ($data as $col => $val) {
            $set[] = "$col = '$val'";
        }
        $set = join(", ", $set);
        $this->query = "UPDATE `$table` SET $set";
        return $this;
    }

    /**
     * Формирует запрос DELETE
     *
     * Далее обязательно нужно вызвать where
     *
     * @api
     * @param string $fromTable Название таблицы, из которой удаляем
     * @return $this
     * @throws RequireParamNotFoundException
     */
    public function delete(string $fromTable): Driver
    {
        if(empty($table)) {
            throw new RequireParamNotFoundException("Не указано название таблицы");
        }
        $this->reset();
        $this->type = QueryType::Delete;
        $this->query = "DELETE FROM `$fromTable` ";
        return $this;
    }

    /**
     * Построение запроса для SELECT, UPDATE и DELETE
     * @throws QuerySequenceBrokenExceptions
     */
    protected function prepareQuery(): void
    {
        if(empty($this->type) || empty($this->query)) {
            throw new QuerySequenceBrokenExceptions();
        }

        $query = $this->query;
        $where = '';
        if(!empty($this->where)){
            $where = ' WHERE ';
            foreach($this->where as $w){
                foreach ($w as $key => $value) {
                    if($key == 0){
                        $where .= "$value[0] $value[1] '$value[2]' ";
                    }else{
                        $where .= "$key $value[0] $value[1] '$value[2]' ";
                    }
                }
            }
        }
        if($this->type === QueryType::Select){
            $orderBy = '';
            if(!empty($this->orderBy)){
                $orderBy = 'ORDER BY ' . join(', ', $this->orderBy);
            }
            $limit = ($this->limit > 0) ? "LIMIT $this->limit" : '';
            $query .=  $where . $orderBy . $limit;
        }else if(in_array($this->type, [QueryType::Select, QueryType::Update, QueryType::Delete])){
            if(empty($where)){
                throw new QuerySequenceBrokenExceptions("Для запроса типа UPDATE и DELETE блок WHERE является обязательным!");
            }
            $query .= $where;
        }
        $this->preparedQuery = $query;
    }
}