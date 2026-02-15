<?php

use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;
use JordJD\uxdm\Objects\Destinations\NDJSONDestination;
use PHPUnit\Framework\TestCase;

final class NDJSONDestinationTest extends TestCase
{
    public function testPutDataRows()
    {
        $file = __DIR__.'/Data/destination.ndjson';

        $dataRows = [];

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('user.name', 'Jordan Hall'));
        $dataRow->addDataItem(new DataItem('user.email', 'jordan@example.com'));
        $dataRows[] = $dataRow;

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('user.name', 'Bob'));
        $dataRow->addDataItem(new DataItem('user.email', 'bob@example.com'));
        $dataRows[] = $dataRow;

        $destination = new NDJSONDestination($file);
        $destination->putDataRows($dataRows);
        $destination->finishMigration();

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $this->assertCount(2, $lines);

        $this->assertEquals(
            ['user' => ['name' => 'Jordan Hall', 'email' => 'jordan@example.com']],
            json_decode($lines[0], true)
        );

        $this->assertEquals(
            ['user' => ['name' => 'Bob', 'email' => 'bob@example.com']],
            json_decode($lines[1], true)
        );
    }
}

