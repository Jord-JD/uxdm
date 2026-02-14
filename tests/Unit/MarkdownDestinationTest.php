<?php

use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;
use JordJD\uxdm\Objects\Destinations\MarkdownDestination;
use PHPUnit\Framework\TestCase;

final class MarkdownDestinationTest extends TestCase
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

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('name', $faker->word.' | '.$faker->word));
        $dataRow->addDataItem(new DataItem('value', $faker->randomNumber));
        $dataRows[] = $dataRow;

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('name', $faker->word));
        $dataRow->addDataItem(new DataItem('value', $faker->randomNumber.' | '.$faker->randomNumber));
        $dataRows[] = $dataRow;

        return $dataRows;
    }

    private function getExpectedFileContent(array $dataRows)
    {
        $expectedFileContent = 'name | value'.PHP_EOL;
        $expectedFileContent .= '--- | --- '.PHP_EOL;

        foreach ($dataRows as $dataRow) {
            $expectedFileContent .= str_replace('|', '\|', $dataRow->getDataItemByFieldName('name')->value);
            $expectedFileContent .= ' | ';
            $expectedFileContent .= str_replace('|', '\|', $dataRow->getDataItemByFieldName('value')->value);
            $expectedFileContent .= PHP_EOL;
        }

        return $expectedFileContent;
    }

    public function testPutDataRows()
    {
        $dataRows = $this->createDataRows();

        $file = __DIR__.'/Data/destination.md';

        $destination = new MarkdownDestination($file);
        $destination->putDataRows($dataRows);
        $destination->finishMigration();

        $fileContent = file_get_contents($file);

        $this->assertEquals($this->getExpectedFileContent($dataRows), $fileContent);
    }
}
