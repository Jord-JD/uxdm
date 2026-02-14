<?php

use JordJD\uxdm\Objects\DataItem;
use PHPUnit\Framework\TestCase;

final class DataItemTest extends TestCase
{
    public function testDataItemCreation()
    {
        $faker = Faker\Factory::create();

        $fieldName = $faker->word;
        $value = $faker->word;

        $dataItem = new DataItem($fieldName, $value);

        $this->assertEquals($dataItem->fieldName, $fieldName);
        $this->assertEquals($dataItem->value, $value);
        $this->assertFalse($dataItem->keyField, false);
    }

    public function testKeyDataItemCreation()
    {
        $faker = Faker\Factory::create();

        $fieldName = $faker->word;
        $value = $faker->word;

        $dataItem = new DataItem($fieldName, $value, true);

        $this->assertEquals($dataItem->fieldName, $fieldName);
        $this->assertEquals($dataItem->value, $value);
        $this->assertTrue($dataItem->keyField);
    }
}
