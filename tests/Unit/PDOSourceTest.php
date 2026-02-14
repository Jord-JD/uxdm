<?php

use JordJD\uxdm\Objects\Sources\PDOSource;
use PHPUnit\Framework\TestCase;

final class PDOSourceTest extends TestCase
{
    private function createSource()
    {
        return new PDOSource(new PDO('sqlite:'.__DIR__.'/Data/source.sqlite'), 'users');
    }

    public function testGetFields()
    {
        $source = $this->createSource();

        $this->assertEquals(['id', 'name', 'email'], $source->getFields());
    }

    public function testGetDataRows()
    {
        $source = $this->createSource();

        $dataRows = $source->getDataRows(1, ['name', 'email']);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(2, $dataItems);

        $this->assertEquals('name', $dataItems[0]->fieldName);
        $this->assertEquals('Tim', $dataItems[0]->value);

        $this->assertEquals('email', $dataItems[1]->fieldName);
        $this->assertEquals('tim@example.com', $dataItems[1]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(2, $dataItems);

        $this->assertEquals('name', $dataItems[0]->fieldName);
        $this->assertEquals('Bear', $dataItems[0]->value);

        $this->assertEquals('email', $dataItems[1]->fieldName);
        $this->assertEquals('bear@example.com', $dataItems[1]->value);

        $dataRows = $source->getDataRows(2, ['name', 'email']);

        $this->assertCount(0, $dataRows);
    }

    public function testGetDataRowsOnlyOneField()
    {
        $source = $this->createSource();

        $dataRows = $source->getDataRows(1, ['email']);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('email', $dataItems[0]->fieldName);
        $this->assertEquals('tim@example.com', $dataItems[0]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('email', $dataItems[0]->fieldName);
        $this->assertEquals('bear@example.com', $dataItems[0]->value);

        $dataRows = $source->getDataRows(2, ['email']);

        $this->assertCount(0, $dataRows);
    }

    public function testOverrideSQLWithoutSelect()
    {
        $source = $this->createSource();

        $this->expectException(Exception::class);

        $source->setOverrideSQL('invalid sql');
    }

    public function testOverrideSQLWithoutLimit()
    {
        $source = $this->createSource();

        $this->expectException(Exception::class);

        $source->setOverrideSQL('select * from users where name = \'Tim\'');
    }

    public function testOverrideSQL()
    {
        $source = $this->createSource();

        $source->setOverrideSQL('select name, email from users where name = \'Tim\' limit ? , ?');

        $dataRows = $source->getDataRows(1);

        $this->assertCount(1, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(2, $dataItems);

        $this->assertEquals('name', $dataItems[0]->fieldName);
        $this->assertEquals('Tim', $dataItems[0]->value);

        $this->assertEquals('email', $dataItems[1]->fieldName);
        $this->assertEquals('tim@example.com', $dataItems[1]->value);
    }

    public function testGetDataRowsPagination()
    {
        $source = $this->createSource();
        $source->setPerPage(1);

        $dataRows = $source->getDataRows(1, ['name', 'email']);

        $this->assertCount(1, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(2, $dataItems);

        $this->assertEquals('name', $dataItems[0]->fieldName);
        $this->assertEquals('Tim', $dataItems[0]->value);

        $this->assertEquals('email', $dataItems[1]->fieldName);
        $this->assertEquals('tim@example.com', $dataItems[1]->value);

        $dataRows = $source->getDataRows(2, ['name', 'email']);

        $this->assertCount(1, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertEquals('name', $dataItems[0]->fieldName);
        $this->assertEquals('Bear', $dataItems[0]->value);

        $this->assertEquals('email', $dataItems[1]->fieldName);
        $this->assertEquals('bear@example.com', $dataItems[1]->value);

        $dataRows = $source->getDataRows(3, ['name', 'email']);

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

    private function createEmptyTableSource()
    {
        return new PDOSource(new PDO('sqlite:'.__DIR__.'/Data/source.sqlite'), 'empty_table');
    }

    public function testGetFieldsOnEmptyTable()
    {
        $source = $this->createEmptyTableSource();

        $this->assertEquals([], $source->getFields());
    }

    public function testGetDataRowsOnEmptyTable()
    {
        $source = $this->createEmptyTableSource();

        $dataRows = $source->getDataRows(1, ['thing1', 'thing2']);

        $this->assertCount(0, $dataRows);
    }
}
