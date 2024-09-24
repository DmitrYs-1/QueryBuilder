> Автор: **Дмитрий Семёнов**\
> *Специально для АИ Технолоджис*

Для подключения к своему проекту используйте следующую команду:
```sh
composer require dmitrys\query-builder:dev-master
```

## Доступные базы данных
#### MySQL `DatabaseType::MySQL`
#### PostgreSQL `DatabaseType::PostgreSQL`
#### SQLite `DatabaseType::SQLite`

## Подключение
```php
<?php
require 'vendor/autoload.php';

use DmitrYs\QueryBuilder\Builder;
use DmitrYs\QueryBuilder\Common\Enum\DatabaseType;

$params = [
    'path' => 'path/to/database.sqlite'
];

$sqlite = Builder::db(DatabaseType::SQLite, $params);


$params = [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'db_name',
    'user'  => 'db_user',
    'password' => 'db_pass'
];

$mysql = Builder::db(DatabaseType::MySQL, $params);


$params = [
    'host' => 'localhost',
    'port' => 5432,
    'database' => 'db_name',
    'user'  => 'db_user',
    'password' => 'db_pass'
];

$pg = Builder::db(DatabaseType::PostgreSQL, $params);
```

### Примеры использования
```php
use DmitrYs\QueryBuilder\Common\Enum\WhereType;
use DmitrYs\QueryBuilder\Common\Enum\OrderByDirection;

$params = [
    'path' => 'path/to/database.sqlite'
];

$db = Builder::db(DatabaseType::SQLite, $params);
$db->select('table', ['col1', 'col2'])
    ->where('col1', '>', 10)
    ->where('col2', '=', 'test-value', WhereType::AND)
    ->orderBy('col1', OrderByDirection::DESC)
    ->limit(3);

$data = $db->getAll(); //Массив всех значений результата, индексированный названиями колонок.
$data = $db->getAll(); //Массив всех значений результата, индексированный названиями колонок.
$data = $db->getValue(); //Значение первого поля первой строки результата. Применимо для запросов, возвращающих единственное значение.
$data = $db->execute(); //Вернёт сырой объект результата в формате выбранной базы данных.

$db->insert('table_name', [
    'column' => 'value'
])->execute();

$db->update('table_name', [
    'column' => 'value'
])->where('col', '=', 'val')->execute();

$db->delete('table_name')->where('col', '=', 'val')->execute();
```

