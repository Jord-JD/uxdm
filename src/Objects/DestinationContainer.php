<?php

namespace JordJD\uxdm\Objects;

use JordJD\uxdm\Interfaces\DestinationInterface;

class DestinationContainer
{
    public $destination;
    public $fields = [];

    public function __construct(DestinationInterface $destination, array $fields)
    {
        $this->destination = $destination;
        $this->fields = $fields;
    }

    public function putDataRows(array $dataRows): void
    {
        if (!$this->fields) {
            $this->destination->putDataRows($dataRows);

            return;
        }

        $destinationDataRows = [];

        foreach ($dataRows as $dataRow) {
            $destinationDataRow = new DataRow();
            foreach ($dataRow->getDataItems() as $dataItem) {
                if (in_array($dataItem->fieldName, $this->fields)) {
                    $destinationDataRow->addDataItem($dataItem);
                }
            }
            $destinationDataRows[] = $destinationDataRow;
        }

        $this->destination->putDataRows($destinationDataRows);
    }

    public function finishMigration(): void
    {
        $this->destination->finishMigration();
    }
}
