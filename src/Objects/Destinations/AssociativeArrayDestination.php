<?php

namespace JordJD\uxdm\Objects\Destinations;

use JordJD\uxdm\Interfaces\DestinationInterface;

class AssociativeArrayDestination implements DestinationInterface
{
    protected $array;

    public function __construct(array &$array)
    {
        $this->array = &$array;
    }

    public function putDataRows(array $dataRows): void
    {
        foreach ($dataRows as $dataRow) {
            $dataItems = $dataRow->getDataItems();

            $row = [];

            foreach ($dataItems as $dataItem) {
                $row[$dataItem->fieldName] = $dataItem->value;
            }

            $this->array[] = $row;
        }
    }

    public function finishMigration(): void
    {
    }
}
