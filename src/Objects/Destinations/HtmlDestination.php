<?php

namespace JordJD\uxdm\Objects\Destinations;

use JordJD\uxdm\Interfaces\DestinationInterface;

class HtmlDestination implements DestinationInterface
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
                    $fieldNames[] = htmlentities($dataItem->fieldName);
                }
                fwrite($fh, '<table class="uxdm-table">');
                fwrite($fh, '<tr class="uxdm-fields"><th class="uxdm-field">');
                fwrite($fh, implode('</th><th class="uxdm-field">', $fieldNames));
                fwrite($fh, '</th></tr>');
            }

            $values = [];
            foreach ($dataItems as $dataItem) {
                $values[] = htmlentities($dataItem->value);
            }
            fwrite($fh, '<tr class="uxdm-values"><td class="uxdm-value">');
            fwrite($fh, implode('</td><td class="uxdm-value">', $values));
            fwrite($fh, '</td></tr>');

            $this->rowNum++;
        }
    }

    public function finishMigration(): void
    {
        $fh = fopen($this->file, 'a');
        fwrite($fh, '</table>');
        fclose($fh);
    }
}
