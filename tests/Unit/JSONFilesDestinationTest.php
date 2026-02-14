<?php

use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;
use JordJD\uxdm\Objects\Destinations\JSONFilesDestination;
use PHPUnit\Framework\TestCase;

final class JSONFilesDestinationTest extends TestCase
{
    private function createDataRows()
    {
        $faker = Faker\Factory::create();

        $dataRows = [];

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('name', $faker->word));
        $dataRow->addDataItem(new DataItem('value', $faker->randomNumber));
        $dataRow->addDataItem(new DataItem('nested.word', $faker->word));
        $dataRow->addDataItem(new DataItem('array.0', $faker->word));
        $dataRow->addDataItem(new DataItem('array.1', $faker->word));
        $dataRow->addDataItem(new DataItem('array.2', $faker->word));
        $dataRow->addDataItem(new DataItem('nested.array.0', $faker->word));
        $dataRow->addDataItem(new DataItem('nested.array.1', $faker->word));
        $dataRow->addDataItem(new DataItem('nested.array.2', $faker->word));
        $dataRows[] = $dataRow;

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('name', $faker->word));
        $dataRow->addDataItem(new DataItem('value', $faker->randomNumber));
        $dataRow->addDataItem(new DataItem('nested.word', $faker->word));
        $dataRow->addDataItem(new DataItem('array.0', $faker->word));
        $dataRow->addDataItem(new DataItem('array.1', $faker->word));
        $dataRow->addDataItem(new DataItem('array.2', $faker->word));
        $dataRow->addDataItem(new DataItem('nested.array.0', $faker->word));
        $dataRow->addDataItem(new DataItem('nested.array.1', $faker->word));
        $dataRow->addDataItem(new DataItem('nested.array.2', $faker->word));
        $dataRows[] = $dataRow;

        return $dataRows;
    }

    private function getExpectedFileContent(DataRow $dataRow)
    {
        $expectedFileContent = '{
    "name": "'.$dataRow->getDataItemByFieldName('name')->value.'",
    "value": '.$dataRow->getDataItemByFieldName('value')->value.',
    "nested": {
        "word": "'.$dataRow->getDataItemByFieldName('nested.word')->value.'",
        "array": [
            "'.$dataRow->getDataItemByFieldName('nested.array.0')->value.'",
            "'.$dataRow->getDataItemByFieldName('nested.array.1')->value.'",
            "'.$dataRow->getDataItemByFieldName('nested.array.2')->value.'"
        ]
    },
    "array": [
        "'.$dataRow->getDataItemByFieldName('array.0')->value.'",
        "'.$dataRow->getDataItemByFieldName('array.1')->value.'",
        "'.$dataRow->getDataItemByFieldName('array.2')->value.'"
    ]
}';

        return $expectedFileContent;
    }

    public function testPutDataRows()
    {
        $dataRows = $this->createDataRows();

        $destination = new JSONFilesDestination(__DIR__.'/Data/JSONFilesDestination/');
        $destination->putDataRows($dataRows);

        $this->assertEquals($this->getExpectedFileContent($dataRows[0]), file_get_contents(__DIR__.'/Data/JSONFilesDestination/1.json'));
        $this->assertEquals($this->getExpectedFileContent($dataRows[1]), file_get_contents(__DIR__.'/Data/JSONFilesDestination/2.json'));
    }
}
