<?php

use JordJD\uxdm\Objects\Sources\WordPressPostSource;
use PHPUnit\Framework\TestCase;

final class WordPressPostSourceTest extends TestCase
{
    private function createSource()
    {
        return new WordPressPostSource(new PDO('sqlite:'.__DIR__.'/Data/wordpress.sqlite'), 'post');
    }

    public function testGetFields()
    {
        $source = $this->createSource();

        $expectedFields = [
            0  => 'wp_posts.ID',
            1  => 'wp_posts.post_author',
            2  => 'wp_posts.post_date',
            3  => 'wp_posts.post_date_gmt',
            4  => 'wp_posts.post_content',
            5  => 'wp_posts.post_title',
            6  => 'wp_posts.post_excerpt',
            7  => 'wp_posts.post_status',
            8  => 'wp_posts.comment_status',
            9  => 'wp_posts.ping_status',
            10 => 'wp_posts.post_name',
            11 => 'wp_posts.to_ping',
            12 => 'wp_posts.pinged',
            13 => 'wp_posts.post_modified',
            14 => 'wp_posts.post_modified_gmt',
            15 => 'wp_posts.post_content_filtered',
            16 => 'wp_posts.post_parent',
            17 => 'wp_posts.guid',
            18 => 'wp_posts.menu_order',
            19 => 'wp_posts.post_type',
            20 => 'wp_posts.post_mime_type',
            21 => 'wp_posts.comment_count',
            22 => 'wp_postmeta.test_key_1',
            23 => 'wp_postmeta.test_key_2',
        ];

        $this->assertEquals($expectedFields, $source->getFields());
    }

    public function testGetDataRows()
    {
        $source = $this->createSource();

        $fields = ['wp_posts.ID', 'wp_posts.post_title', 'wp_posts.post_content', 'wp_postmeta.test_key_1', 'wp_postmeta.test_key_2'];

        $dataRows = $source->getDataRows(1, $fields);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(5, $dataItems);

        $this->assertEquals('wp_posts.ID', $dataItems[0]->fieldName);
        $this->assertEquals('1', $dataItems[0]->value);

        $this->assertEquals('wp_posts.post_title', $dataItems[1]->fieldName);
        $this->assertEquals('Test title 1', $dataItems[1]->value);

        $this->assertEquals('wp_posts.post_content', $dataItems[2]->fieldName);
        $this->assertEquals('Test content 1', $dataItems[2]->value);

        $this->assertEquals('wp_postmeta.test_key_1', $dataItems[3]->fieldName);
        $this->assertEquals('test_value_1', $dataItems[3]->value);

        $this->assertEquals('wp_postmeta.test_key_2', $dataItems[4]->fieldName);
        $this->assertEquals('test_value_2', $dataItems[4]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(4, $dataItems);

        $this->assertEquals('wp_posts.ID', $dataItems[0]->fieldName);
        $this->assertEquals('2', $dataItems[0]->value);

        $this->assertEquals('wp_posts.post_title', $dataItems[1]->fieldName);
        $this->assertEquals('Test title 2', $dataItems[1]->value);

        $this->assertEquals('wp_posts.post_content', $dataItems[2]->fieldName);
        $this->assertEquals('Test content 2', $dataItems[2]->value);

        $this->assertEquals('wp_postmeta.test_key_1', $dataItems[3]->fieldName);
        $this->assertEquals('test_value_3', $dataItems[3]->value);

        $dataRows = $source->getDataRows(2, $fields);

        $this->assertCount(0, $dataRows);
    }

    public function testGetDataRowsOnlyOneField()
    {
        $source = $this->createSource();

        $fields = ['wp_posts.post_title'];

        $dataRows = $source->getDataRows(1, $fields);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('wp_posts.post_title', $dataItems[0]->fieldName);
        $this->assertEquals('Test title 1', $dataItems[0]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('wp_posts.post_title', $dataItems[0]->fieldName);
        $this->assertEquals('Test title 2', $dataItems[0]->value);

        $dataRows = $source->getDataRows(2, $fields);

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
