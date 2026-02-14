# PDO Source

The UXDM PDO source allows you to source data from one or more database tables. It supports
any database type that you can create a [PHP Database Object](https://secure.php.net/manual/en/book.pdo.php) for.

## Creating

To create a new PDO source, you must provide it with a PDO object and the name of the table
you wish to source data from. 

The following example creates a new PDO object for a `test` database on the localhost with username `root` and password `password123`. It then creates a PDO source object, using the newly created PDO object and the table name `users` to source data from.

```php
$pdo = new PDO('mysql:dbname=test;host=127.0.0.1', 'root', 'password123');
$pdoSource = new PDOSource($pdo, 'users');
```

## Assigning to migrator

To use the PDO source as part of a UXDM migration, you must assign it to the migrator. This process is the same for most sources.

```php
$migrator = new Migrator;
$migrator->setSource($pdoSource);
```

## Overriding SQL

By default the PDO source will generate its own SQL statements to access table data. However,
if you wish, you can override this SQL to access the data differently.

```php
$sql = 'SELECT * FROM users LIMIT ? , ?';
$pdoSource->setOverrideSQL($sql);
```

Please note: You must include `SELECT` and `LIMIT ? , ?` in the override SQL.

## Join to another table

PDO sources allow retrieval of data from multiple tables. You can do this via overriding SQL
or by adding joins. You can add a join to another table quite easily. See the following example.

```php
use JordJD\uxdm\Objects\Sources\PDO\Join;
$pdoSource->addJoin(new Join('table_to_join_to', 'join_to_key', 'join_from_key'));
```

## Set per page

The PDO source will automatically paginate data retrieval by use of `LIMIT`. You can alter
how many records are retrieved at once by altering the 'per page' value. You can increase the
value to speed up data retrieval, and you can decrease the value to reduce memory usage.

```php
$pdoSource->setPerPage(100);
```