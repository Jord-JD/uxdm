<?php

namespace JordJD\uxdm\Objects\Destinations;

use JordJD\uxdm\Interfaces\DestinationInterface;

class CSVDestination implements DestinationInterface
{
    protected $file;
    protected $rowNum = 0;
    protected $fieldNames;

    /**
     * @param string     $file
     * @param null|array $fieldNames Optional list of field/column names. When provided, output will always include
     *                               these columns (in the given order), with blanks for missing values.
     */
    public function __construct($file, ?array $fieldNames = null)
    {
        $this->file = $file;
        $this->fieldNames = $fieldNames;
    }

    public function putDataRows(array $dataRows): void
    {
        if (!$dataRows) {
            return;
        }

        $fh = fopen($this->file, $this->rowNum === 0 ? 'w' : 'a');

        if ($this->rowNum === 0) {
            if (!$this->fieldNames) {
                // Build a stable header from the union of fields present in the first batch.
                // This prevents missing columns when later rows omit optional fields.
                $this->fieldNames = [];

                foreach ($dataRows as $dataRow) {
                    foreach ($dataRow->getDataItems() as $dataItem) {
                        if (!in_array($dataItem->fieldName, $this->fieldNames, true)) {
                            $this->fieldNames[] = $dataItem->fieldName;
                        }
                    }
                }
            }

            fputcsv($fh, $this->fieldNames);
        }

        foreach ($dataRows as $dataRow) {
            $row = $dataRow->toArray();

            $values = [];
            foreach ($this->fieldNames as $fieldName) {
                $values[] = $row[$fieldName] ?? '';
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
