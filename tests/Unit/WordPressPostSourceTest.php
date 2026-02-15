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

    public function testSetPerPageAffectsPagination()
    {
        $source = $this->createSource();
        $source->setPerPage(1);

        $this->assertEquals(2, $source->countPages());

        $dataRows = $source->getDataRows(1, ['wp_posts.ID']);
        $this->assertCount(1, $dataRows);

        $dataRows = $source->getDataRows(2, ['wp_posts.ID']);
        $this->assertCount(1, $dataRows);
    }

    public function testWithTermsAddsFieldsAndValues()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec('create table wp_posts (ID integer primary key, post_type text, post_title text, post_content text)');
        $pdo->exec('create table wp_postmeta (meta_id integer primary key autoincrement, post_id integer, meta_key text, meta_value text)');
        $pdo->exec('create table wp_terms (term_id integer primary key, slug text, name text)');
        $pdo->exec('create table wp_term_taxonomy (term_taxonomy_id integer primary key, term_id integer, taxonomy text)');
        $pdo->exec('create table wp_term_relationships (object_id integer, term_taxonomy_id integer)');

        $pdo->exec("insert into wp_posts (ID, post_type, post_title, post_content) values (1, 'post', 'Post 1', 'Content 1')");
        $pdo->exec("insert into wp_posts (ID, post_type, post_title, post_content) values (2, 'post', 'Post 2', 'Content 2')");

        $pdo->exec("insert into wp_postmeta (post_id, meta_key, meta_value) values (1, 'test_key_1', 'test_value_1')");

        $pdo->exec("insert into wp_terms (term_id, slug, name) values (1, 'cat-a', 'Cat A')");
        $pdo->exec("insert into wp_terms (term_id, slug, name) values (2, 'tag-a', 'Tag A')");

        $pdo->exec("insert into wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy) values (1, 1, 'category')");
        $pdo->exec("insert into wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy) values (2, 2, 'post_tag')");

        $pdo->exec('insert into wp_term_relationships (object_id, term_taxonomy_id) values (1, 1)');
        $pdo->exec('insert into wp_term_relationships (object_id, term_taxonomy_id) values (1, 2)');
        $pdo->exec('insert into wp_term_relationships (object_id, term_taxonomy_id) values (2, 1)');

        $source = new WordPressPostSource($pdo, 'post');
        $source->withTerms(['category', 'post_tag']);

        $this->assertContains('wp_terms.category', $source->getFields());
        $this->assertContains('wp_terms.post_tag', $source->getFields());

        $fields = ['wp_posts.ID', 'wp_terms.category', 'wp_terms.post_tag'];
        $dataRows = $source->getDataRows(1, $fields);

        $this->assertCount(2, $dataRows);

        $dataItems = $dataRows[0]->getDataItems();
        $this->assertEquals('wp_posts.ID', $dataItems[0]->fieldName);
        $this->assertEquals('1', $dataItems[0]->value);

        $this->assertEquals('wp_terms.category', $dataItems[1]->fieldName);
        $this->assertEquals('cat-a', $dataItems[1]->value);

        $this->assertEquals('wp_terms.post_tag', $dataItems[2]->fieldName);
        $this->assertEquals('tag-a', $dataItems[2]->value);

        $dataItems = $dataRows[1]->getDataItems();
        $this->assertEquals('wp_posts.ID', $dataItems[0]->fieldName);
        $this->assertEquals('2', $dataItems[0]->value);

        $this->assertEquals('wp_terms.category', $dataItems[1]->fieldName);
        $this->assertEquals('cat-a', $dataItems[1]->value);

        $this->assertEquals('wp_terms.post_tag', $dataItems[2]->fieldName);
        $this->assertEquals('', $dataItems[2]->value);
    }
}
