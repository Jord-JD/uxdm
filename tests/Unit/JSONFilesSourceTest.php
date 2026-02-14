<?php

use JordJD\uxdm\Objects\Sources\JSONFilesSource;
use PHPUnit\Framework\TestCase;

final class JSONFilesSourceTest extends TestCase
{
    private function createSource()
    {
        $files = glob(__DIR__.'/Data/JSONFiles/*.json');

        return new JSONFilesSource($files);
    }

    public function testGetFields()
    {
        $source = $this->createSource();

        $expectedFields = ['_file', 'datePlaced', 'customer', 'billingAddress', 'deliveryAddress', 'items.0.product.id',
            'items.0.product.data.slug', 'items.0.product.data.name', 'items.0.product.data.description',
            'items.0.product.data.imageUrls', 'items.0.product.data.prices.0.value',
            'items.0.product.data.prices.0.currency', 'items.0.product.data.prices.1.value',
            'items.0.product.data.prices.1.currency', 'items.0.product.data.categoryIds.0',
            'items.0.product.data.draft', 'items.0.product.data.deletedAt', 'items.0.quantity', 'items.0.unitCost',
            'items.0.total', 'currency', 'subtotal', 'deliveryOption', 'total', ];

        $this->assertEquals($expectedFields, $source->getFields());
    }

    public function testGetFileName()
    {
        $source = $this->createSource();

        $dataRows = $source->getDataRows(1, ['_file']);

        $count = 1;

        foreach ($dataRows as $dataRow) {
            $dataItem = $dataRow->getDataItemByFieldName('_file');
            $expectedFileName = __DIR__.'/Data/JSONFiles/'.$count.'.json';

            $this->assertEquals($expectedFileName, $dataItem->value);

            $count++;
        }
    }

    public function testGetDataRows()
    {
        $source = $this->createSource();

        $dataRows = $source->getDataRows(1, ['total', 'currency']);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(2, $dataItems);

        $this->assertEquals('total', $dataItems[1]->fieldName);
        $this->assertEquals(25.98, $dataItems[1]->value);

        $this->assertEquals('currency', $dataItems[0]->fieldName);
        $this->assertEquals('GBP', $dataItems[0]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(2, $dataItems);

        $this->assertEquals('total', $dataItems[1]->fieldName);
        $this->assertEquals(51.96, $dataItems[1]->value);

        $this->assertEquals('currency', $dataItems[0]->fieldName);
        $this->assertEquals('GBP', $dataItems[0]->value);

        $dataRows = $source->getDataRows(2, ['name', 'weight']);

        $this->assertCount(0, $dataRows);
    }

    public function testGetDataRowsOnlyOneField()
    {
        $source = $this->createSource();

        $dataRows = $source->getDataRows(1, ['datePlaced']);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('datePlaced', $dataItems[0]->fieldName);
        $this->assertEquals(1443087352, $dataItems[0]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('datePlaced', $dataItems[0]->fieldName);
        $this->assertEquals(1443087698, $dataItems[0]->value);

        $dataRows = $source->getDataRows(2, ['datePlaced']);

        $this->assertCount(0, $dataRows);
    }

    public function testAccessToArrays()
    {
        $source = $this->createSource();

        $dataRows = $source->getDataRows(1, ['items.0.quantity']);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('items.0.quantity', $dataItems[0]->fieldName);
        $this->assertEquals(2, $dataItems[0]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('items.0.quantity', $dataItems[0]->fieldName);
        $this->assertEquals(4, $dataItems[0]->value);

        $dataRows = $source->getDataRows(2, ['name', 'weight']);

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
