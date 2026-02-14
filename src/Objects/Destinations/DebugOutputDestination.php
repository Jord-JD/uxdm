<?php

namespace JordJD\uxdm\Objects\Destinations;

use JordJD\uxdm\Interfaces\DestinationInterface;

class DebugOutputDestination implements DestinationInterface
{
    public function putDataRows(array $dataRows): void
    {
        var_dump($dataRows);
    }

    public function finishMigration(): void
    {
    }
}
