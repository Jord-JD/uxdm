<?php

use JordJD\OmegaValidator\Rules\IsEmail;
use JordJD\OmegaValidator\Rules\IsString;
use JordJD\OmegaValidator\Rules\Required;
use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;
use JordJD\uxdm\Objects\Exceptions\NoDataItemsInDataRowException;
use JordJD\uxdm\Objects\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class DataRowTest extends TestCase
{
    private function createDataItem()
    {
        $faker = Faker\Factory::create();

        $fieldName = $faker->word;
        $value = $faker->word;

        $dataItem = new DataItem($fieldName, $value);

        return $dataItem;
    }

    public function testDataRowCreation()
    {
        $dataRow = new DataRow();

        $this->assertCount(0, $dataRow->getDataItems());
        $this->assertCount(0, $dataRow->getKeyDataItems());
    }

    public function testDataItemAddition()
    {
        $dataItem = $this->createDataItem();

        $dataRow = new DataRow();
        $dataRow->addDataItem($dataItem);

        $this->assertCount(1, $dataRow->getDataItems());
        $this->assertCount(0, $dataRow->getKeyDataItems());

        $dataItems = $dataRow->getDataItems();

        $this->assertEquals($dataItems[0], $dataItem);
    }

    public function testKeyDataItemAddition()
    {
        $dataItem = $this->createDataItem();
        $dataItem->keyField = true;

        $dataRow = new DataRow();
        $dataRow->addDataItem($dataItem);

        $this->assertCount(1, $dataRow->getDataItems());
        $this->assertCount(1, $dataRow->getKeyDataItems());

        $dataItems = $dataRow->getDataItems();

        $this->assertEquals($dataItems[0], $dataItem);
    }

    public function testDataItemRemoval()
    {
        $dataItem = $this->createDataItem();

        $dataRow = new DataRow();
        $dataRow->addDataItem($dataItem);

        $this->assertCount(1, $dataRow->getDataItems());

        $dataRow->removeDataItem($dataItem);

        $this->assertCount(0, $dataRow->getDataItems());
    }

    public function testGetDataItemByFieldName()
    {
        $dataItem = $this->createDataItem();

        $dataRow = new DataRow();
        $dataRow->addDataItem($dataItem);

        $dataItemRetrieved = $dataRow->getDataItemByFieldName($dataItem->fieldName);

        $this->assertEquals($dataItemRetrieved, $dataItem);
    }

    public function testGetDataItemByFieldNameFailure()
    {
        $dataItem = $this->createDataItem();

        $dataRow = new DataRow();
        $dataRow->addDataItem($dataItem);

        $dataItemRetrieved = $dataRow->getDataItemByFieldName($dataItem->fieldName.'_invalid');

        $this->assertEquals($dataItemRetrieved, null);
    }

    public function testKeyFieldsPreparation()
    {
        $dataItem = $this->createDataItem();

        $dataRow = new DataRow();
        $dataRow->addDataItem($dataItem);

        $keyFields = [$dataItem->fieldName];
        $fieldMap = [];
        $dataItemManipulator = function () {
        };

        $dataRow->prepare([], $keyFields, $fieldMap, $dataItemManipulator);

        $dataItemRetrieved = $dataRow->getDataItemByFieldName($dataItem->fieldName);

        $this->assertTrue($dataItemRetrieved->keyField);
    }

    public function testFieldMapPreparation()
    {
        $dataItem = $this->createDataItem();

        $dataRow = new DataRow();
        $dataRow->addDataItem($dataItem);

        $faker = Faker\Factory::create();
        $newFieldName = $faker->word;

        $keyFields = [];
        $fieldMap = [$dataItem->fieldName => $newFieldName];
        $dataItemManipulator = function () {
        };

        $dataRow->prepare([], $keyFields, $fieldMap, $dataItemManipulator);

        $dataItemRetrieved = $dataRow->getDataItemByFieldName($dataItem->fieldName);

        $this->assertEquals($dataItemRetrieved->fieldName, $newFieldName);
    }

    public function testValidationOfEmptyDataRow()
    {
        $this->expectException(NoDataItemsInDataRowException::class);

        $dataRow = new DataRow();

        $keyFields = [];
        $fieldMap = [];
        $dataItemManipulator = function () {
        };

        $dataRow->prepare([], $keyFields, $fieldMap, $dataItemManipulator);
    }

    public function testValidationRulesFailure()
    {
        $expectedExceptionMessageArray = [
            'email' => [
                IsEmail::class => 'The email must be a valid email address.',
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(print_r($expectedExceptionMessageArray, true));

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('email', 'not-an-email'));

        $rules = ['email' => [new Required(), new IsString(), new IsEmail()]];
        $keyFields = [];
        $fieldMap = [];
        $dataItemManipulator = function () {
        };

        $dataRow->prepare($rules, $keyFields, $fieldMap, $dataItemManipulator);
    }

    public function testValidationRulesSuccess()
    {
        $this->expectNotToPerformAssertions();

        $dataRow = new DataRow();
        $dataRow->addDataItem(new DataItem('email', 'test@example.com'));

        $rules = ['email' => [new Required(), new IsString(), new IsEmail()]];
        $keyFields = [];
        $fieldMap = [];
        $dataItemManipulator = function () {
        };

        $dataRow->prepare($rules, $keyFields, $fieldMap, $dataItemManipulator);
    }
}
