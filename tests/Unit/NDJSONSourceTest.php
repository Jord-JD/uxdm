<?php

use JordJD\uxdm\Objects\Sources\NDJSONSource;
use PHPUnit\Framework\TestCase;

final class NDJSONSourceTest extends TestCase
{
    private function createSource()
    {
        return new NDJSONSource(__DIR__.'/Data/source.ndjson');
    }

    public function testGetFields()
    {
        $source = $this->createSource();

        $this->assertEquals(['Title', 'Author.Name'], $source->getFields());
    }

    public function testGetDataRows()
    {
        $source = $this->createSource();

        $fields = ['Title', 'Author.Name'];
        $dataRows = $source->getDataRows(1, $fields);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();
        $this->assertCount(2, $dataItems);
        $this->assertEquals('Title', $dataItems[0]->fieldName);
        $this->assertEquals('Adventures Of Me', $dataItems[0]->value);
        $this->assertEquals('Author.Name', $dataItems[1]->fieldName);
        $this->assertEquals('Jordan Hall', $dataItems[1]->value);

        $dataItems = $dataRows[1]->getDataItems();
        $this->assertCount(2, $dataItems);
        $this->assertEquals('Title', $dataItems[0]->fieldName);
        $this->assertEquals('All The Things', $dataItems[0]->value);
        $this->assertEquals('Author.Name', $dataItems[1]->fieldName);
        $this->assertEquals('Mr Bear', $dataItems[1]->value);

        $dataRows = $source->getDataRows(2, $fields);
        $this->assertCount(0, $dataRows);
    }

    public function testCountDataRowsAndPages()
    {
        $source = $this->createSource();

        $this->assertEquals(2, $source->countDataRows());
        $this->assertEquals(1, $source->countPages());
    }
}

