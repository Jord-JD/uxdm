# NDJSON Destination

The UXDM NDJSON destination allows you to write migrated data to an NDJSON (newline-delimited JSON) file.

Each data row is written as one JSON object per line.

## Creating

To create a new NDJSON destination, you must provide the path to the output NDJSON file.

```php
$ndjsonDestination = new NDJSONDestination(__DIR__.'/output.ndjson');
```

## Assigning to migrator

To use the NDJSON destination as part of a UXDM migration, assign it to the migrator:

```php
$migrator = new Migrator;
$migrator->setDestination($ndjsonDestination);
```

