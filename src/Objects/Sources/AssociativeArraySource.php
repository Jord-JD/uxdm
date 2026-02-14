<?php

namespace JordJD\uxdm\Objects\Sources;

use JordJD\uxdm\Interfaces\SourceInterface;
use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;

class AssociativeArraySource implements SourceInterface
{
    protected $array;
    protected $fields = [];
    protected $perPage = 10;

    public function __construct(array &$array)
    {
        $this->array = &$array;

        if (isset($array[0]) && is_array($array[0])) {
            $this->fields = array_keys($array[0]);
        }
    }

    public function getDataRows(int $page = 1, array $fieldsToRetrieve = []): array
    {
        $offset = 0 + (($page - 1) * $this->perPage);

        $arrayRows = array_slice($this->array, $offset, $this->perPage);

        $dataRows = [];

        foreach ($arrayRows as $arrayRow) {
            $dataRow = new DataRow();

            foreach ($arrayRow as $key => $value) {
                if (in_array($key, $fieldsToRetrieve)) {
                    $dataRow->addDataItem(new DataItem($key, $value));
                }
            }

            $dataRows[] = $dataRow;
        }

        return $dataRows;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function countDataRows(): int
    {
        return count($this->array);
    }

    public function countPages(): int
    {
        return ceil($this->countDataRows() / $this->perPage);
    }
}
