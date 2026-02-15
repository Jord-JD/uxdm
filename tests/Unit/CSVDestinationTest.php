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

    public function testPutDataRowsWritesBlankColumnsForMissingValues()
    {
        $file = __DIR__.'/Data/destination.missing_blanks.csv';

        $dataRows = [];

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('BaseVehicle', '1995, Ford, Explorer'));
        $dataRow->addDataItem(new DataItem('DriveType', '4WD'));
        $dataRow->addDataItem(new DataItem('Part', '14077'));
        $dataRows[] = $dataRow;

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('BaseVehicle', '2017, Honda, Odyssey'));
        $dataRow->addDataItem(new DataItem('Part', '143305'));
        $dataRows[] = $dataRow;

        $destination = new CSVDestination($file);
        $destination->putDataRows($dataRows);
        $destination->finishMigration();

        $fileContent = file_get_contents($file);

        $expectedFileContent = 'BaseVehicle,DriveType,Part'.PHP_EOL;
        $expectedFileContent .= '"1995, Ford, Explorer",4WD,14077'.PHP_EOL;
        $expectedFileContent .= '"2017, Honda, Odyssey",,143305'.PHP_EOL;

        $this->assertEquals($expectedFileContent, $fileContent);
    }
}
