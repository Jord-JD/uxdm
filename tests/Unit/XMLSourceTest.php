<?php

use JordJD\uxdm\Objects\Sources\XMLSource;
use PHPUnit\Framework\TestCase;

final class XMLSourceTest extends TestCase
{
    private function createSource()
    {
        $xmlSource = new XMLSource(__DIR__.'/Data/source.xml', '/ns:urlset/ns:url');
        $xmlSource->addXMLNamespace('ns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return $xmlSource;
    }

    public function testCreationOfXMLSourceWithIncorrectXPath()
    {
        $this->expectException(Exception::class);

        $xmlSource = new XMLSource(__DIR__.'/Data/source.xml', '/ns:urlsAt/ns:url');
        $xmlSource->addXMLNamespace('ns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $xmlSource->getFields();
    }

    public function testGetFields()
    {
        $source = $this->createSource();

        $this->assertEquals(['#text', 'loc', 'lastmod', 'changefreq', 'priority'], $source->getFields());
    }

    public function testGetDataRows()
    {
        $source = $this->createSource();

        $dataRows = $source->getDataRows(1, ['loc', 'lastmod']);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(2, $dataItems);

        $this->assertEquals('loc', $dataItems[0]->fieldName);
        $this->assertEquals('https://www.rapidweb.biz/', $dataItems[0]->value);

        $this->assertEquals('lastmod', $dataItems[1]->fieldName);
        $this->assertEquals('2017-06-05T23:08:55+00:00', $dataItems[1]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(2, $dataItems);

        $this->assertEquals('loc', $dataItems[0]->fieldName);
        $this->assertEquals('https://www.rapidweb.biz/web-design.html', $dataItems[0]->value);

        $this->assertEquals('lastmod', $dataItems[1]->fieldName);
        $this->assertEquals('2017-06-05T23:08:55+00:00', $dataItems[1]->value);

        $dataRows = $source->getDataRows(2, ['loc', 'lastmod']);

        $this->assertCount(0, $dataRows);
    }

    public function testGetDataRowsOnlyOneField()
    {
        $source = $this->createSource();

        $dataRows = $source->getDataRows(1, ['priority']);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('priority', $dataItems[0]->fieldName);
        $this->assertEquals('1.0000', $dataItems[0]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('priority', $dataItems[0]->fieldName);
        $this->assertEquals('0.8000', $dataItems[0]->value);

        $dataRows = $source->getDataRows(2, ['priority']);

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
