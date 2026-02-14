<?php

namespace JordJD\uxdm\Objects\Destinations;

use JordJD\uxdm\Interfaces\DestinationInterface;

class NullDestination implements DestinationInterface
{
    public function putDataRows(array $dataRows): void
    {
    }

    public function finishMigration(): void
    {
    }
}
