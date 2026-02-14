<?php

use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;
use JordJD\uxdm\Objects\Destinations\CSVDestination;
use PHPUnit\Framework\TestCase;

final class CSVDestinationTest extends TestCase
{
    private function createDataRows()
    {
        $faker = Faker\Factory::create();

        $dataRows = [];

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('name', $faker->word));
        $dataRow->addDataItem(new DataItem('value', $faker->randomNumber));
        $dataRows[] = $dataRow;

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('name', $faker->word));
        $dataRow->addDataItem(new DataItem('value', $faker->randomNumber));
        $dataRows[] = $dataRow;

        return $dataRows;
    }

    private function getExpectedFileContent(array $dataRows)
    {
        $expectedFileContent = 'name,value'.PHP_EOL;

        foreach ($dataRows as $dataRow) {
            $expectedFileContent .= $dataRow->getDataItemByFieldName('name')->value;
            $expectedFileContent .= ',';
            $expectedFileContent .= $dataRow->getDataItemByFieldName('value')->value;
            $expectedFileContent .= PHP_EOL;
        }

        return $expectedFileContent;
    }

    public function testPutDataRows()
    {
        $dataRows = $this->createDataRows();

        $file = __DIR__.'/Data/destination.csv';

        $destination = new CSVDestination($file);
        $destination->putDataRows($dataRows);
        $destination->finishMigration();

        $fileContent = file_get_contents($file);

        $this->assertEquals($this->getExpectedFileContent($dataRows), $fileContent);
    }
}
