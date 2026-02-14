<?php

namespace JordJD\uxdm\Objects\Destinations;

use JordJD\uxdm\Interfaces\DestinationInterface;

class MarkdownDestination implements DestinationInterface
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
                fwrite($fh, implode(' | ', $fieldNames).PHP_EOL);
                fwrite($fh, substr(str_repeat('--- | ', count($fieldNames)), 0, -2).PHP_EOL);
            }

            $values = [];
            foreach ($dataItems as $dataItem) {
                $values[] = str_replace('|', '\\|', $dataItem->value);
            }
            fwrite($fh, implode(' | ', $values).PHP_EOL);

            $this->rowNum++;
        }

        fclose($fh);
    }

    public function finishMigration(): void
    {
    }
}
