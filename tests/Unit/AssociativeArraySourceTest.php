<?php

use JordJD\uxdm\Objects\Sources\AssociativeArraySource;
use PHPUnit\Framework\TestCase;

final class AssociativeArraySourceTest extends TestCase
{
    private function createSource()
    {
        $associativeArray = [
            ['name' => 'Thing', 'weight' => 2, 'value' => 900],
            ['name' => 'Bear', 'weight' => 5000, 'value' => 0],
        ];

        return new AssociativeArraySource($associativeArray);
    }

    public function testGetFields()
    {
        $source = $this->createSource();

        $this->assertEquals(['name', 'weight', 'value'], $source->getFields());
    }

    public function testGetDataRows()
    {
        $source = $this->createSource();

        $dataRows = $source->getDataRows(1, ['name', 'weight']);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(2, $dataItems);

        $this->assertEquals('name', $dataItems[0]->fieldName);
        $this->assertEquals('Thing', $dataItems[0]->value);

        $this->assertEquals('weight', $dataItems[1]->fieldName);
        $this->assertEquals(2, $dataItems[1]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(2, $dataItems);

        $this->assertEquals('name', $dataItems[0]->fieldName);
        $this->assertEquals('Bear', $dataItems[0]->value);

        $this->assertEquals('weight', $dataItems[1]->fieldName);
        $this->assertEquals(5000, $dataItems[1]->value);

        $dataRows = $source->getDataRows(2, ['name', 'weight']);

        $this->assertCount(0, $dataRows);
    }

    public function testGetDataRowsOnlyOneField()
    {
        $source = $this->createSource();

        $dataRows = $source->getDataRows(1, ['value']);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('value', $dataItems[0]->fieldName);
        $this->assertEquals(900, $dataItems[0]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('value', $dataItems[0]->fieldName);
        $this->assertEquals(0, $dataItems[0]->value);

        $dataRows = $source->getDataRows(2, ['value']);

        $this->assertCount(0, $dataRows);
    }

    public function testCountDataRows()
    {
        $source = $this->createSource();

        $this->assertEquals(2, $source->countDataRows());
    }

    public function testCountPages()
    {
        $source = $this->createSource();

        $this->assertEquals(1, $source->countPages());
    }
}
