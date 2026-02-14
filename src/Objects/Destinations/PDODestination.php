<?php

namespace JordJD\uxdm\Objects\Destinations;

use JordJD\uxdm\Interfaces\DestinationInterface;
use JordJD\uxdm\Objects\DataRow;
use PDO;
use PDOException;

class PDODestination implements DestinationInterface
{
    protected $pdo;
    protected $tableName;
    protected $ignoreIntegrityConstraintViolations;
    protected $transactions = true;

    public function __construct(PDO $pdo, $tableName)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    public function ignoreIntegrityConstraintViolations($active = true)
    {
        $this->ignoreIntegrityConstraintViolations = $active;

        return $this;
    }

    public function disableTransactions()
    {
        $this->transactions = false;

        return $this;
    }

    private function rowAlreadyExists(array $keyDataItems)
    {
        $sql = 'select count(*) as c from '.$this->tableName.' where ';

        foreach ($keyDataItems as $keyDataItem) {
            $sql .= $keyDataItem->fieldName.' = ? and ';
        }

        $sql = substr($sql, 0, -4);

        $stmt = $this->pdo->prepare($sql);

        $paramNum = 1;
        foreach ($keyDataItems as $keyDataItem) {
            $stmt->bindValue($paramNum, $keyDataItem->value);
            $paramNum++;
        }

        $stmt->execute();

        $result = $stmt->fetchObject();

        if ($result->c > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function insertDataRow(DataRow $dataRow)
    {
        $dataItems = $dataRow->getDataItems();

        $sql = 'insert into '.$this->tableName.' (';

        foreach ($dataItems as $dataItem) {
            $sql .= $dataItem->fieldName.' , ';
        }
        $sql = substr($sql, 0, -2);

        $sql .= ') values (';

        for ($i = 0; $i < count($dataItems); $i++) {
            $sql .= '? , ';
        }
        $sql = substr($sql, 0, -2);

        $sql .= ')';

        $stmt = $this->pdo->prepare($sql);

        $paramNum = 1;
        foreach ($dataItems as $dataItem) {
            $stmt->bindValue($paramNum, $dataItem->value);
            $paramNum++;
        }

        $stmt->execute();
    }

    private function updateDataRow(DataRow $dataRow)
    {
        $sql = 'update '.$this->tableName.' set ';

        $dataItems = $dataRow->getDataItems();

        foreach ($dataItems as $dataItem) {
            $sql .= $dataItem->fieldName.' = ? , ';
        }

        $sql = substr($sql, 0, -2);

        $sql .= 'where ';

        $keyDataItems = $dataRow->getKeyDataItems();

        foreach ($keyDataItems as $keyDataItem) {
            $sql .= $keyDataItem->fieldName.' = ? and ';
        }

        $sql = substr($sql, 0, -4);

        $stmt = $this->pdo->prepare($sql);

        $paramNum = 1;
        foreach ($dataItems as $dataItem) {
            $stmt->bindValue($paramNum, $dataItem->value);
            $paramNum++;
        }
        foreach ($keyDataItems as $keyDataItem) {
            $stmt->bindValue($paramNum, $keyDataItem->value);
            $paramNum++;
        }

        $stmt->execute();
    }

    public function putDataRows(array $dataRows): void
    {
        if ($this->transactions) {
            $this->pdo->beginTransaction();
        }

        foreach ($dataRows as $dataRow) {
            $keyDataItems = $dataRow->getKeyDataItems();

            try {
                if (!$keyDataItems) {
                    $this->insertDataRow($dataRow);
                    continue;
                }

                if ($this->rowAlreadyExists($keyDataItems)) {
                    $this->updateDataRow($dataRow);
                } else {
                    $this->insertDataRow($dataRow);
                }
            } catch (PDOException $e) {
                if ($this->ignoreIntegrityConstraintViolations && $e->getCode() == 23000) {
                    continue;
                }

                throw $e;
            }
        }

        if ($this->transactions) {
            $this->pdo->commit();
        }
    }

    public function finishMigration(): void
    {
    }
}
