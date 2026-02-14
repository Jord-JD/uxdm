<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use JordJD\uxdm\Objects\Destinations\CSVDestination;
use JordJD\uxdm\Objects\Migrator;
use JordJD\uxdm\Objects\Sources\XMLSource;

$xmlSource = new XMLSource(__DIR__.'/source.xml', '/ns:urlset/ns:url');
$xmlSource->addXMLNamespace('ns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

$csvDestination = new CSVDestination(__DIR__.'/destination.csv');

$migrator = new Migrator();
$migrator->setSource($xmlSource)
         ->setDestination($csvDestination)
         ->setFieldsToMigrate(['loc'])
         ->withProgressBar()
         ->migrate();
