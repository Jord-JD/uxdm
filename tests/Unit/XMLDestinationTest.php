<?php

use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;
use JordJD\uxdm\Objects\Destinations\XMLDestination;
use PHPUnit\Framework\TestCase;

final class XMLDestinationTest extends TestCase
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
        $dataRow->addDataItem(new DataItem('name', 'special_characters_test_&<>'));
        $dataRow->addDataItem(new DataItem('value', $faker->randomNumber));
        $dataRows[] = $dataRow;

        return $dataRows;
    }

    private function getExpectedFileContent(array $dataRows)
    {
        $expectedFileContent = '<?xml version="1.0"?>'.PHP_EOL;
        $expectedFileContent .= '<root>';

        foreach ($dataRows as $dataRow) {
            $expectedFileContent .= '<dataRow><name>';
            $expectedFileContent .= htmlspecialchars($dataRow->getDataItemByFieldName('name')->value);
            $expectedFileContent .= '</name><value>';
            $expectedFileContent .= htmlspecialchars($dataRow->getDataItemByFieldName('value')->value);
            $expectedFileContent .= '</value></dataRow>';
        }

        $expectedFileContent .= '</root>'.PHP_EOL;

        return $expectedFileContent;
    }

    public function testPutDataRows()
    {
        $dataRows = $this->createDataRows();

        $file = __DIR__.'/Data/destination.xml';

        $domDoc = new DOMDocument();
        $rootElement = $domDoc->appendChild(new DOMElement('root'));

        $destination = new XMLDestination($file, $domDoc, $rootElement, 'dataRow');
        $destination->putDataRows($dataRows);
        $destination->finishMigration();

        $fileContent = file_get_contents($file);

        $this->assertEquals($this->getExpectedFileContent($dataRows), $fileContent);
    }
}
