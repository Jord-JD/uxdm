<?php

namespace JordJD\uxdm\Objects\Destinations;

use JordJD\uxdm\Interfaces\DestinationInterface;

class JSONFilesDestination implements DestinationInterface
{
    protected $directory;
    protected $fileNum = 1;

    public function __construct($directory)
    {
        $this->directory = realpath($directory);
    }

    public function putDataRows(array $dataRows): void
    {
        foreach ($dataRows as $dataRow) {
            $dataItems = $dataRow->getDataItems();

            $row = [];

            foreach ($dataItems as $dataItem) {
                $row[$dataItem->fieldName] = $dataItem->value;
            }

            $array = array_undot($row);

            $filePath = $this->directory.'/'.$this->fileNum.'.json';

            file_put_contents($filePath, json_encode($array, JSON_PRETTY_PRINT));

            $this->fileNum++;
        }
    }

    public function finishMigration(): void
    {
    }
}
