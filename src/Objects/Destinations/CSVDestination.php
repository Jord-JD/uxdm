<?php

namespace JordJD\uxdm\Objects\Destinations;

use JordJD\uxdm\Interfaces\DestinationInterface;

class CSVDestination implements DestinationInterface
{
    protected $file;
    protected $rowNum = 0;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function putDataRows(array $dataRows): void
    {
        if ($this->rowNum === 0) {
            $fh = fopen($this->file, 'w');
        } else {
            $fh = fopen($this->file, 'a');
        }

        foreach ($dataRows as $dataRow) {
            $dataItems = $dataRow->getDataItems();

            if ($this->rowNum === 0) {
                $fieldNames = [];
                foreach ($dataItems as $dataItem) {
                    $fieldNames[] = $dataItem->fieldName;
                }
                fputcsv($fh, $fieldNames);
            }

            $values = [];
            foreach ($dataItems as $dataItem) {
                $values[] = $dataItem->value;
            }
            fputcsv($fh, $values);

            $this->rowNum++;
        }

        fclose($fh);
    }

    public function finishMigration(): void
    {
    }
}
