<?php

namespace JordJD\uxdm\Interfaces;

interface DestinationInterface
{
    public function putDataRows(array $dataRows): void;

    public function finishMigration(): void;
}
