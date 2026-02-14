<?php

use Cache\Adapter\PHPArray\ArrayCachePool;
use JordJD\uxdm\Objects\Destinations\PDODestination;
use JordJD\uxdm\Objects\Exceptions\MissingFieldToMigrateException;
use JordJD\uxdm\Objects\Exceptions\NoDestinationException;
use JordJD\uxdm\Objects\Exceptions\NoSourceException;
use JordJD\uxdm\Objects\Migrator;
use JordJD\uxdm\Objects\Sources\PDOSource;
use JordJD\uxdm\TestIntegrationClasses\Md5NameTransformer;
use JordJD\uxdm\TestIntegrationClasses\UppercaseNameTransformer;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    private $pdo;
    private $pdo2;

    private function getPDOSource()
    {
        return new PDOSource(new PDO('sqlite:'.__DIR__.'/Data/source.sqlite'), 'users');
    }

    private function getPDODestination()
    {
        $this->pdo = new PDO('sqlite::memory:');

        $sql = 'DROP TABLE IF EXISTS migrator_test';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $sql = 'CREATE TABLE IF NOT EXISTS migrator_test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, md5_name TEXT, email_address TEXT)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return new PDODestination($this->pdo, 'migrator_test');
    }

    private function getPDODestination2()
    {
        $this->pdo2 = new PDO('sqlite::memory:');

        $sql = 'DROP TABLE IF EXISTS migrator_test2';
        $stmt = $this->pdo2->prepare($sql);
        $stmt->execute();

        $sql = 'CREATE TABLE IF NOT EXISTS migrator_test2 (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)';
        $stmt = $this->pdo2->prepare($sql);
        $stmt->execute();

        return new PDODestination($this->pdo2, 'migrator_test2');
    }

    private function getActualArray()
    {
        $sql = 'SELECT * FROM migrator_test';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    private function getExpectedArray()
    {
        $expected = [];
        $expected[0] = [
            'id'            => 2,
            'name'          => 'BEAR',
            'md5_name'      => 'e699d5afb08b7a16fb4e9c707353fe48',
            'email_address' => 'bear@example.com',
        ];

        return $expected;
    }

    private function getActualArray2()
    {
        $sql = 'SELECT * FROM migrator_test2';
        $stmt = $this->pdo2->prepare($sql);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    private function getExpectedArray2()
    {
        $expected = [];
        $expected[0] = [
            'id'   => 2,
            'name' => 'BEAR',
        ];

        return $expected;
    }

    public function testMigrator()
    {
        $migrator = new Migrator();

        $migrator->setSource($this->getPDOSource())
                 ->setDestination($this->getPDODestination())
                 ->setFieldsToMigrate(['id', 'name', 'email'])
                 ->setKeyFields(['id'])
                 ->setFieldMap(['email' => 'email_address'])
                 ->addTransformer(new UppercaseNameTransformer())
                 ->addTransformer(new Md5NameTransformer())
                 ->setSkipIfTrueCheck(function ($dataRow) {
                     return $dataRow->getDataItemByFieldName('name')->value == 'TIM';
                 })
                 ->migrate();

        $this->assertEquals($this->getExpectedArray(), $this->getActualArray());
    }

    public function testMigratorWithProgressBar()
    {
        $migrator = new Migrator();

        ob_start();

        $migrator->setSource($this->getPDOSource())
                 ->setDestination($this->getPDODestination())
                 ->setFieldsToMigrate(['id', 'name', 'email'])
                 ->setKeyFields(['id'])
                 ->setFieldMap(['email' => 'email_address'])
                 ->addTransformer(new UppercaseNameTransformer())
                 ->addTransformer(new Md5NameTransformer())
                 ->setSkipIfTrueCheck(function ($dataRow) {
                     return $dataRow->getDataItemByFieldName('name')->value == 'TIM';
                 })
                 ->withProgressBar()
                 ->migrate();

        $progressBarOutput = ob_get_clean();
        $expectedProgressBarOutput = file_get_contents(__DIR__.'/expectedProgressBarOutput.txt');

        $this->assertEquals($this->getExpectedArray(), $this->getActualArray());
        $this->assertEquals($expectedProgressBarOutput, $progressBarOutput);
    }

    public function testMigratorWithNoSource()
    {
        $this->expectException(NoSourceException::class);

        $migrator = new Migrator();
        $migrator->migrate();
    }

    public function testMigratorWithNoDestination()
    {
        $this->expectException(NoDestinationException::class);

        $migrator = new Migrator();
        $migrator->setSource($this->getPDOSource())
                 ->migrate();
    }

    public function testMigratorWithKeyFieldThatIsNotPresentInFieldsToMigrate()
    {
        $this->expectException(MissingFieldToMigrateException::class);

        $migrator = new Migrator();
        $migrator->setSource($this->getPDOSource())
                 ->setDestination($this->getPDODestination())
                 ->setFieldsToMigrate(['email'])
                 ->setKeyFields(['id'])
                 ->migrate();
    }

    public function testMigratorWithMigrateMapSourceFieldThatIsNotPresentInFieldsToMigrate()
    {
        $this->expectException(MissingFieldToMigrateException::class);

        $migrator = new Migrator();
        $migrator->setSource($this->getPDOSource())
                 ->setDestination($this->getPDODestination())
                 ->setFieldsToMigrate(['id'])
                 ->setFieldMap(['email' => 'email_address'])
                 ->migrate();
    }

    public function testMigratorWithNoFieldsToMigrate()
    {
        $migrator = new Migrator();

        $migrator->setSource($this->getPDOSource())
                 ->setDestination($this->getPDODestination())
                 ->setKeyFields(['id'])
                 ->setFieldMap(['email' => 'email_address'])
                 ->addTransformer(new UppercaseNameTransformer())
                 ->addTransformer(new Md5NameTransformer())
                 ->setSkipIfTrueCheck(function ($dataRow) {
                     return $dataRow->getDataItemByFieldName('name')->value == 'TIM';
                 })
                 ->migrate();

        $this->assertEquals($this->getExpectedArray(), $this->getActualArray());
    }

    public function testMigratorWithMultipleDestinations()
    {
        $migrator = new Migrator();

        $migrator->setSource($this->getPDOSource())
                 ->addDestination($this->getPDODestination())
                 ->addDestination($this->getPDODestination2(), ['id', 'name'])
                 ->setFieldsToMigrate(['id', 'name', 'email'])
                 ->setKeyFields(['id'])
                 ->setFieldMap(['email' => 'email_address'])
                 ->addTransformer(new UppercaseNameTransformer())
                 ->addTransformer(new Md5NameTransformer())
                 ->setSkipIfTrueCheck(function ($dataRow) {
                     return $dataRow->getDataItemByFieldName('name')->value == 'TIM';
                 })
                 ->migrate();

        $this->assertEquals($this->getExpectedArray(), $this->getActualArray());
        $this->assertEquals($this->getExpectedArray2(), $this->getActualArray2());
    }

    public function testMigratorWithCache()
    {
        $cache = new ArrayCachePool();

        $migrator = new Migrator();

        $migrator->setSource($this->getPDOSource())
                 ->setSourceCache($cache, 'testCache', 60 * 60 * 24)
                 ->setDestination($this->getPDODestination())
                 ->setFieldsToMigrate(['id', 'name', 'email'])
                 ->setKeyFields(['id'])
                 ->setFieldMap(['email' => 'email_address'])
                 ->addTransformer(new UppercaseNameTransformer())
                 ->addTransformer(new Md5NameTransformer())
                 ->setSkipIfTrueCheck(function ($dataRow) {
                     return $dataRow->getDataItemByFieldName('name')->value == 'TIM';
                 })
                 ->migrate();

        $this->assertEquals($this->getExpectedArray(), $this->getActualArray());

        $migrator->setSource($this->getPDOSource())
                 ->setSourceCache($cache, 'testCache', 60 * 60 * 24)
                 ->setDestination($this->getPDODestination())
                 ->setFieldsToMigrate(['id', 'name', 'email'])
                 ->setKeyFields(['id'])
                 ->setFieldMap(['email' => 'email_address'])
                 ->addTransformer(new UppercaseNameTransformer())
                 ->addTransformer(new Md5NameTransformer())
                 ->setSkipIfTrueCheck(function ($dataRow) {
                     return $dataRow->getDataItemByFieldName('name')->value == 'TIM';
                 })
                 ->migrate();

        $this->assertEquals($this->getExpectedArray(), $this->getActualArray());
    }
}
