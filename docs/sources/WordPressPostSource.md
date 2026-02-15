# WordPress Post Source

The UXDM WordPress post source allows you to source post data and post meta data from a WordPress database.

## Creating

To create a new WordPress post source, you must provide it with a PDO object that points towards the WordPress database. 

The following example creates a new WordPress post object for a `wordpress` database on the localhost with username `root` and password `password123`. 
It then creates a WordPress post source object, using the newly created PDO object.

```php
$pdo = new PDO('mysql:dbname=wordpress;host=127.0.0.1', 'root', 'password123');
$wordPressPostSource = new WordPressPostSource($pdo);
```

If you wish, you can also change the table prefix, as shown below. If not changed, it defaults to `wp_`.

```php
$wordPressPostSource->setTablePrefix('wp2_');
```

If you wish, you can also change how many posts are retrieved per page (default is 10).

```php
$wordPressPostSource->setPerPage(100);
```

## Assigning to migrator

To use the WordPress post source as part of a UXDM migration, you must assign it to the migrator. This process is the same for most sources.

```php
$migrator = new Migrator;
$migrator->setSource($wordPressPostSource);
```

## Field names and prefixes

WordPress sources return field names prefixed with the WordPress table prefix and table name, for example:

* `wp_posts.ID`
* `wp_posts.post_title`
* `wp_postmeta._edit_lock`

When using the WordPress sources, you should use these full field names (including prefixes) in:

* `setFieldsToMigrate()`
* `setKeyFields()`
* `setFieldMap()`

To see the full list of fields available for your WordPress database, call:

```php
$fields = $wordPressPostSource->getFields();
```

## Using a custom post type

By default the WordPress post source will only retrieve posts of type `post`. If you wish, you can retrieve a different post type, such as
`page` or `product`. To do so, just specify the custom post type as the second parameter when constructing the WordPress post source.

See the snippet below.

```php
$pdo = new PDO('mysql:dbname=wordpress;host=127.0.0.1', 'root', 'password123');
$wordPressPostSource = new WordPressPostSource($pdo, 'product');
```

## Including terms (categories/tags)

If you wish, you can include WordPress terms (such as categories and tags) for each post.

This adds additional fields to the source, with values being a separator-delimited list of term slugs.

```php
$wordPressPostSource->withTerms(['category', 'post_tag']);
```

You can optionally change the separator (default is `,`).

```php
$wordPressPostSource->setTermsSeparator('|');
```
