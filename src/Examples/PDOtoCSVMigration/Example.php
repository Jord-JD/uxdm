<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use JordJD\uxdm\Objects\Destinations\CSVDestination;
use JordJD\uxdm\Objects\Migrator;
use JordJD\uxdm\Objects\Sources\PDOSource;

$pdo = new PDO('mysql:dbname=laravel-test;host=127.0.0.1', 'root', getenv('UXDM_EXAMPLE_PASSWORD'));
$pdoSource = new PDOSource($pdo, 'users');
$csvDestination = new CSVDestination(__DIR__.'/destination.csv');
$emailsCSVDestination = new CSVDestination(__DIR__.'/emails.csv');

$migrator = new Migrator();
$migrator->setSource($pdoSource)
         ->addDestination($csvDestination)
         ->addDestination($emailsCSVDestination, ['email'])
         ->setFieldsToMigrate(['email', 'name'])
         ->withProgressBar()
         ->migrate();
