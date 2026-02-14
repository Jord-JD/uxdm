<?php

namespace JordJD\uxdm\Interfaces;

interface SourceInterface
{
    public function getDataRows(int $page = 1, array $fieldsToRetrieve = []): array;

    public function countDataRows(): int;

    public function countPages(): int;

    public function getFields(): array;
}
