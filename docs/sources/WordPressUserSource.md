# WordPress User Source

The UXDM WordPress user source allows you to source user data and user meta data from a WordPress database.

## Creating

To create a new WordPress user source, you must provide it with a PDO object that points towards the WordPress database. 

The following example creates a new WordPress user object for a `wordpress` database on the localhost with username `root` and password `password123`. 
It then creates a WordPress user source object, using the newly created PDO object.

```php
$pdo = new PDO('mysql:dbname=wordpress;host=127.0.0.1', 'root', 'password123');
$wordPressUserSource = new WordPressUserSource($pdo);
```

If you wish, you can also change the table prefix, as shown below. If not changed, it defaults to `wp_`.

```php
$wordPressUserSource->setTablePrefix('wp2_');
```

If you wish, you can also change how many users are retrieved per page (default is 10).

```php
$wordPressUserSource->setPerPage(100);
```

## Assigning to migrator

To use the WordPress user source as part of a UXDM migration, you must assign it to the migrator. This process is the same for most sources.

```php
$migrator = new Migrator;
$migrator->setSource($wordPressUserSource);
```

## Field names and prefixes

WordPress sources return field names prefixed with the WordPress table prefix and table name, for example:

* `wp_users.ID`
* `wp_users.user_login`
* `wp_usermeta.first_name`

When using the WordPress sources, you should use these full field names (including prefixes) in:

* `setFieldsToMigrate()`
* `setKeyFields()`
* `setFieldMap()`

To see the full list of fields available for your WordPress database, call:

```php
$fields = $wordPressUserSource->getFields();
```
