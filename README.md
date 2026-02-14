# 🔀 Universal Extensible Data Migrator (UXDM)

UXDM helps developers migrate data from one system or format to another.

```php
// Setup your source and destination objects.
// This example uses database connections, but UXDM supports many different source and destination formats.
$pdoSource = new PDOSource(new PDO('mysql:dbname=old-test;host=localhost', 'un', 'pw'), 'users');
$pdoDestination = new PDODestination(new PDO('mysql:dbname=new-test;host=localhost', 'un', 'pw'), 'new_users');

// Create a new migrator
$migrator = new Migrator;
$migrator->setSource($pdoSource)                      // Source
         ->setDestination($pdoDestination)            // Destination
         ->setFieldsToMigrate(['id', 'email', 'name']) // Fields to migrate
         ->setKeyFields(['id'])                       // Key(s) used to identify unique rows
         ->setFieldMap(['name' => 'full_name'])       // Mapping of field names, from source to destination
         ->withProgressBar()                          // Show progress bar, when run in CLI.
         ->migrate();                                 // Do the migration!
```

<p align="center">
    <a href="https://github.com/Jord-JD/uxdm/actions/workflows/phpunit.yml"><img src="https://github.com/Jord-JD/uxdm/actions/workflows/phpunit.yml/badge.svg?branch=master"></a>
    <a href="https://coveralls.io/github/Jord-JD/uxdm?branch=master"><img src="https://coveralls.io/repos/github/Jord-JD/uxdm/badge.svg?branch=master"></a>
    <a href="https://styleci.io/repos/130364449"><img src="https://styleci.io/repos/130364449/shield?branch=master"></a>
    <a href="https://packagist.org/packages/jord-jd/uxdm/stats"><img src="https://img.shields.io/packagist/dt/Jord-JD/uxdm.svg"></a>
</p>

## Installation

UXDM can be easily installed using Composer. Just run the following command from the root of your project.

```
composer require jord-jd/uxdm
```

If you have never used the Composer dependency manager before, head to the [Composer website](https://getcomposer.org/) for more information on how to get started.

## Quick Start

1. Create a new PHP file to contain your UXDM migration code. In this example, we'll call it `user-csv-import.php`. Remember to add `require 'vendor/autoload.php'` and relevant `use` statements, if necessary.

2. Create your source and destination objects. This example uses a CSV source and PDO (database) destination.

```php
$csvSource = new CSVSource('users.csv');

$pdoDestination = new PDODestination(new PDO('mysql:dbname=test-database;host=127.0.0.1', 'root', 'password'), 'users');
```

3. Create and configure a new UXDM migrator object.

```php
$migrator = new Migrator;
$migrator->setSource($csvSource)
         ->setDestination($pdoDestination)
         ->setFieldsToMigrate(['id', 'email', 'name'])
         ->setKeyFields(['id'])
         ->withProgressBar()
         ->migrate();
```

4. Run your newly created migration. In this example, we can just run `php user-csv-import.php` from the command line and will get a nice progress bar.

See the sections below for more information on the available source and destination objects, and more advanced usage examples.

## Migrations

Each UXDM migration requires a source object and at least one destination object. These determine where and how data is read and written. 
The UXDM package works with a variety of source and destination objects, including the following.

* PDO (PHP Database Object) Source & Destination
* Eloquent (as used in Laravel) Source & Destination
* Doctrine (as used in Symfony) Destination
* CSV (Comma Separated Values) Source & Destination
* Excel Source & Destination
* Associative Array Source & Destination
* JSON Files Source & Destination
* XML Source & Destination
* WordPress Post Source
* WordPress User Source
* Debug Output Destination

Some of these are built-in to the core UXDM package, while others are available as separate packages.

Source and destination objects can be used in any combination. Data can be migrated from a CSV and inserted into a database, just as easily as data can be migrated from a database to a CSV.

You can also use similar source and destination objects in the same migration. For example, a common use of UXDM is to use a PDO source and PDO destination to transfer data from one database to another. 

Please see the [Sources & Destinations](/docs/uxdm-sources-and-destinations.md) page for more sources and destinations, and detailed documentation on their usage.

## Examples

### Database to database migration

An example of a basic database to database UXDM migration is shown below.

```php
$pdoSource = new PDOSource(new PDO('mysql:dbname=old-test;host=127.0.0.1', 'root', 'password123'), 'users');

$pdoDestination = new PDODestination(new PDO('mysql:dbname=new-test;host=127.0.0.1', 'root', 'password456'), 'new_users');

$migrator = new Migrator;
$migrator->setSource($pdoSource)
         ->setDestination($pdoDestination)
         ->setFieldsToMigrate(['id', 'email', 'name'])
         ->setKeyFields(['id'])
         ->withProgressBar()
         ->migrate();
```

This migration will move the `id`, `email` and `name` fields from the `users` table in the `old-test` database to the `new_users` table in the `new-test` database, replacing any existing records with the same `id` (the key field).

### Source data validation

You can use UXDM to validate the source data. If validation fails part way through a migration, the migration will 
halt and a `ValidationException` will be thrown. However, if `->validateBeforeMigrating()` is called, all data rows
will be preemptively validated before the migration begins.

The code below shows how to validate various fields.

```php
$pdoSource = new PDOSource(new PDO('mysql:dbname=old-test;host=127.0.0.1', 'root', 'password123'), 'users');

$pdoDestination = new PDODestination(new PDO('mysql:dbname=new-test;host=127.0.0.1', 'root', 'password456'), 'new_users');

$migrator = new Migrator;
$migrator->setSource($pdoSource)
         ->setDestination($pdoDestination)
         ->setFieldsToMigrate(['id', 'email', 'name'])
         ->setValidationRules([
            'id' => [new Required(), new IsNumeric()],
            'email' => [new Required(), new IsString(), new IsEmail()],
            'name' => [new Required(), new IsString()],
         ])
      // ->validateBeforeMigrating()
         ->setKeyFields(['id'])
         ->withProgressBar()
         ->migrate();
```

This migration will validate the source data matches the defined validation rules.

* 'id' must be present, and numeric.
* 'email' must be present, a string, and a correctly formatted email address.
* 'name' must be present, and a string.

UXDM uses the [Omega Validator](https://github.com/Jord-JD/omega-validator) package. 
See its documentation for all available validation rules.

### Mapping field names from source to destination

This example shows how UXDM can map field names from source to destination.

```php
$migrator = new Migrator;
$migrator->setSource($pdoSource)
         ->setDestination($pdoDestination)
         ->setFieldsToMigrate(['id', 'email', 'name'])
         ->setKeyFields(['id'])
         ->setFieldMap(['name' => 'full_name'])
         ->withProgressBar()
         ->migrate();
```

This migration will move data from the source `name` field into the destination `full_name` field, while still moving the `id` and `email` fields normally.

### Transforming data rows during migration

Sometimes the data you want to move from source to destination needs transforming. This can be 
changing existing items of data, adding new data items, or removing items you do not need.

UXDM allows you to create one or more transformer objects, and add them to the migration.
See the following examples of how to use transformers to manipulate your data. 

#### Changing existing data items

This example shows how you can transform existing data items during migration.

```php
class NameCaseTransformer implements TransformerInterface
{
    public function transform(DataRow $dataRow): void
    {
        $nameDataItem = $dataRow->getDataItemByFieldName('name');
        $nameDataItem->value = ucwords(strtolower($nameDataItem->value));
    }
}

$migrator = new Migrator;
$migrator->setSource($pdoSource)
         ->setDestination($pdoDestination)
         ->setFieldsToMigrate(['id', 'email', 'name'])
         ->setKeyFields(['id'])
         ->addTransformer(new NameCaseTransformer())
         ->withProgressBar()
         ->migrate();
```

This migration will ensure that all name fields have consistent case.

#### Adding data items

This example shows how you can add new data items while the migration is taking place.

```php
class AddRandomNumberTransformer implements TransformerInterface
{
    public function transform(DataRow &$dataRow): void
    {
        $dataRow->addDataItem(new DataItem('random_number', rand(1,1000)));
    }
}

$migrator = new Migrator;
$migrator->setSource($pdoSource)
         ->setDestination($pdoDestination)
         ->setFieldsToMigrate(['id', 'email', 'name'])
         ->setKeyFields(['id'])
         ->addTransformer(new AddRandomNumberTransformer())
         ->withProgressBar()
         ->migrate();
```

This migration will add a random number into a field called `random_number` for each row of data. 
This will then be migrated to the destination database along with the other fields.

#### Removing data items

This example demonstrates how data items can be removed from a data row. 
You may wish to do this if you want to use its value, but not actually 
migrate it to the destination.

```php
class EmailToHashTransformer implements TransformerInterface
{
    public function transform(DataRow $dataRow): void
    {
        $emailDataItem = $dataRow->getDataItemByFieldName('email');
        $dataRow->addDataItem(new DataItem('email_hash', md5($emailDataItem->value)));
        $dataRow->removeDataItem($emailDataItem);
    }
}

$migrator = new Migrator;
$migrator->setSource($pdoSource)
         ->setDestination($pdoDestination)
         ->setFieldsToMigrate(['id', 'email', 'name'])
         ->setKeyFields(['id'])
         ->addTransformer(new EmailToHashTransformer())
         ->withProgressBar()
         ->migrate();
```

This migration gets the data from the `email` field in the source, creates a 
new `email_hash` data item which contains an md5 of the email address, and then 
removes the original `email` data item. This new `email_hash` will then be 
migrated to the destination database along with the other fields, excluding 
the removed `email` field.
