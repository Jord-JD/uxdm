<?php

use JordJD\uxdm\Objects\Sources\WordPressUserSource;
use PHPUnit\Framework\TestCase;

final class WordPressUserSourceTest extends TestCase
{
    private function createSource()
    {
        return new WordPressUserSource(new PDO('sqlite:'.__DIR__.'/Data/wordpress.sqlite'));
    }

    public function testGetFields()
    {
        $source = $this->createSource();

        $expectedFields = [
            0  => 'wp_users.ID',
            1  => 'wp_users.user_login',
            2  => 'wp_users.user_pass',
            3  => 'wp_users.user_nicename',
            4  => 'wp_users.user_email',
            5  => 'wp_users.user_url',
            6  => 'wp_users.user_registered',
            7  => 'wp_users.user_activation_key',
            8  => 'wp_users.user_status',
            9  => 'wp_users.display_name',
            10 => 'wp_usermeta.test_key_1',
            11 => 'wp_usermeta.test_key_2',
        ];

        $this->assertEquals($expectedFields, $source->getFields());
    }

    public function testGetDataRows()
    {
        $source = $this->createSource();

        $fields = ['wp_users.ID', 'wp_users.user_login', 'wp_users.user_email', 'wp_usermeta.test_key_1', 'wp_usermeta.test_key_2'];

        $dataRows = $source->getDataRows(1, $fields);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(5, $dataItems);

        $this->assertEquals('wp_users.ID', $dataItems[0]->fieldName);
        $this->assertEquals('1', $dataItems[0]->value);

        $this->assertEquals('wp_users.user_login', $dataItems[1]->fieldName);
        $this->assertEquals('jordan', $dataItems[1]->value);

        $this->assertEquals('wp_users.user_email', $dataItems[2]->fieldName);
        $this->assertEquals('jordan@example.com', $dataItems[2]->value);

        $this->assertEquals('wp_usermeta.test_key_1', $dataItems[3]->fieldName);
        $this->assertEquals('test_value_1', $dataItems[3]->value);

        $this->assertEquals('wp_usermeta.test_key_2', $dataItems[4]->fieldName);
        $this->assertEquals('test_value_2', $dataItems[4]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(4, $dataItems);

        $this->assertEquals('wp_users.ID', $dataItems[0]->fieldName);
        $this->assertEquals('2', $dataItems[0]->value);

        $this->assertEquals('wp_users.user_login', $dataItems[1]->fieldName);
        $this->assertEquals('bob', $dataItems[1]->value);

        $this->assertEquals('wp_users.user_email', $dataItems[2]->fieldName);
        $this->assertEquals('bob@example.com', $dataItems[2]->value);

        $this->assertEquals('wp_usermeta.test_key_1', $dataItems[3]->fieldName);
        $this->assertEquals('test_value_3', $dataItems[3]->value);

        $dataRows = $source->getDataRows(2, $fields);

        $this->assertCount(0, $dataRows);
    }

    public function testGetDataRowsOnlyOneField()
    {
        $source = $this->createSource();

        $fields = ['wp_users.user_email'];

        $dataRows = $source->getDataRows(1, $fields);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('wp_users.user_email', $dataItems[0]->fieldName);
        $this->assertEquals('jordan@example.com', $dataItems[0]->value);

        $dataItems = $dataRows[1]->getDataItems();

        $this->assertCount(1, $dataItems);

        $this->assertEquals('wp_users.user_email', $dataItems[0]->fieldName);
        $this->assertEquals('bob@example.com', $dataItems[0]->value);

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

    public function testSetPerPageAffectsPagination()
    {
        $source = $this->createSource();
        $source->setPerPage(1);

        $this->assertEquals(2, $source->countPages());

        $dataRows = $source->getDataRows(1, ['wp_users.ID']);
        $this->assertCount(1, $dataRows);

        $dataRows = $source->getDataRows(2, ['wp_users.ID']);
        $this->assertCount(1, $dataRows);
    }
}
