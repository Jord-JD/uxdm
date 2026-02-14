<?php

namespace JordJD\uxdm\Objects\Sources;

use JordJD\uxdm\Interfaces\SourceInterface;
use Exception;
use PDO;

class MSSQLSource extends PDOSource implements SourceInterface
{
    public function setOverrideSQL($overrideSQL): SourceInterface
    {
        $selectString = 'SELECT ';

        if (stripos($overrideSQL, $selectString) === false) {
            throw new Exception('PDO Source Override SQL must contain \''.$selectString.'\' to select source data.');
        }

        $limitString = 'ORDER BY (SELECT NULL) OFFSET ? ROWS FETCH NEXT ? ROWS ONLY';

        if (stripos($overrideSQL, $limitString) === false) {
            throw new Exception('PDO Source Override SQL must contain \''.$limitString.'\' to allow for pagination of source data.');
        }

        $this->overrideSQL = $overrideSQL;
        $this->fields = $this->getTableFields();

        return $this;
    }

    protected function getSQL($fieldsToRetrieve)
    {
        $fieldsSQL = implode(', ', $fieldsToRetrieve);

        $sql = 'select '.$fieldsSQL.' from '.$this->tableName;

        foreach ($this->joins as $join) {
            $sql .= $join->getSQL();
        }

        $sql .= ' ORDER BY (SELECT NULL) OFFSET ? ROWS FETCH NEXT ? ROWS ONLY';

        if ($this->overrideSQL) {
            $sql = $this->overrideSQL;
        }

        return $sql;
    }

    public function countDataRows(): int
    {
        $sql = $this->getSQL([]);
        $fromPos = stripos($sql, 'from');
        $limitPos = strripos($sql, 'ORDER BY (SELECT NULL) OFFSET');
        $sqlSuffix = substr($sql, $fromPos, $limitPos - $fromPos);

        $sql = 'select count(*) as countDataRows '.$sqlSuffix;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['countDataRows'];
    }
}
