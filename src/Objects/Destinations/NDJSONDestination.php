<?php

namespace JordJD\uxdm\Objects\Destinations;

use JordJD\uxdm\Interfaces\DestinationInterface;

/**
 * NDJSON (newline-delimited JSON) destination.
 *
 * Writes one JSON object per line.
 */
class NDJSONDestination implements DestinationInterface
{
    protected $file;
    protected $rowNum = 0;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function putDataRows(array $dataRows): void
    {
        if (!$dataRows) {
            return;
        }

        $fh = fopen($this->file, $this->rowNum === 0 ? 'w' : 'a');

        foreach ($dataRows as $dataRow) {
            $array = array_undot($dataRow->toArray());

            fwrite($fh, json_encode($array).PHP_EOL);

            $this->rowNum++;
        }

        fclose($fh);
    }

    public function finishMigration(): void
    {
    }
}

