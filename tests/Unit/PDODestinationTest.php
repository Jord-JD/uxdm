<?php

use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;
use JordJD\uxdm\Objects\Destinations\PDODestination;
use PHPUnit\Framework\TestCase;

final class PDODestinationTest extends TestCase
{
    private $pdo = null;

    private function getPDODestination()
    {
        $this->pdo = new PDO('sqlite::memory:');

        $sql = 'DROP TABLE IF EXISTS pdo_destination_test';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $sql = 'CREATE TABLE IF NOT EXISTS pdo_destination_test (name TEXT, value INTEGER)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return new PDODestination($this->pdo, 'pdo_destination_test');
    }

    private function createDataRows()
    {
        $faker = Faker\Factory::create();

        $dataRows = [];

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('name', $faker->word, true));
        $dataRow->addDataItem(new DataItem('value', $faker->randomNumber));
        $dataRows[] = $dataRow;

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('name', $faker->word, true));
        $dataRow->addDataItem(new DataItem('value', $faker->randomNumber));
        $dataRows[] = $dataRow;

        return $dataRows;
    }

    private function alterDataRows(array $dataRows)
    {
        $faker = Faker\Factory::create();

        foreach ($dataRows as $dataRow) {
            $dataItem = $dataRow->getDataItemByFieldName('value');
            $dataItem->value = $faker->randomNumber;
        }

        return $dataRows;
    }

    private function getActualArray()
    {
        $sql = 'SELECT * FROM pdo_destination_test';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    private function getExpectedArray(array $dataRows)
    {
        $expectedArray = [];

        foreach ($dataRows as $dataRow) {
            $expectedArrayRow = [];
            foreach ($dataRow->getDataItems() as $dataItem) {
                $expectedArrayRow[$dataItem->fieldName] = $dataItem->value;
            }
            $expectedArray[] = $expectedArrayRow;
        }

        return $expectedArray;
    }

    public function testPutDataRows()
    {
        $dataRows = $this->createDataRows();

        $destination = $this->getPDODestination();

        $destination->putDataRows($dataRows);

        $this->assertEquals($this->getExpectedArray($dataRows), $this->getActualArray());

        $dataRows = $this->alterDataRows($dataRows);

        $destination->putDataRows($dataRows);

        $this->assertEquals($this->getExpectedArray($dataRows), $this->getActualArray());
    }
}
