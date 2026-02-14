<?php

use JordJD\uxdm\Objects\Sources\PDO\Join;
use PHPUnit\Framework\TestCase;

final class PDOJoinTest extends TestCase
{
    public function testGetJoinSQL()
    {
        $table = 'table1';
        $key = 'key2';
        $foreignKey = 'foreignKey3';

        $join = new Join($table, $key, $foreignKey);

        $expectedSQL = ' INNER JOIN '.$table.' ON '.$key.' = '.$foreignKey;

        $this->assertEquals($expectedSQL, $join->getSQL());
    }
}
