<?php

namespace JordJD\uxdm\Objects\Sources\PDO;

class Join
{
    private $table;
    private $key;
    private $foreignKey;

    public function __construct($table, $key, $foreignKey)
    {
        $this->table = $table;
        $this->key = $key;
        $this->foreignKey = $foreignKey;
    }

    public function getSQL()
    {
        return ' INNER JOIN '.$this->table.' ON '.$this->key.' = '.$this->foreignKey;
    }
}
