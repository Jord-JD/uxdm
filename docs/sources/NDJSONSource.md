# NDJSON Source

The UXDM NDJSON source allows you to source data from an NDJSON (newline-delimited JSON) file.

Each non-empty line in the file must contain valid JSON and represents one data row. JSON objects are flattened to
dot notation field names using `array_dot()`, matching the behaviour of the JSON files source.

## Creating

To create a new NDJSON source, you must provide the path to the NDJSON file.

```php
$ndjsonSource = new NDJSONSource(__DIR__.'/input.ndjson');
```

## Assigning to migrator

To use the NDJSON source as part of a UXDM migration, assign it to the migrator:

```php
$migrator = new Migrator;
$migrator->setSource($ndjsonSource);
```

## Pagination

NDJSON sources support pagination via `setPerPage()`:

```php
$ndjsonSource->setPerPage(100);
```

