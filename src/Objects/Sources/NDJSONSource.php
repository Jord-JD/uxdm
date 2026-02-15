<?php

namespace JordJD\uxdm\Objects\Sources;

use JordJD\uxdm\Interfaces\SourceInterface;
use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;

/**
 * NDJSON (newline-delimited JSON) source.
 *
 * Each non-empty line in the input file must be valid JSON and represents a single data row.
 * Objects are flattened using array_dot(), matching JSONFilesSource behaviour.
 */
class NDJSONSource implements SourceInterface
{
    protected $file;
    protected $fields = [];
    protected $perPage = 10;

    /** @var resource|null */
    protected $fh;
    protected $currentPage = 0;
    protected $nextRowIndex = 0;

    public function __construct($file)
    {
        $this->file = $file;
        $this->fields = $this->computeFields();
    }

    public function __destruct()
    {
        $this->closeHandle();
    }

    public function setPerPage(int $perPage): self
    {
        $this->perPage = $perPage;
        $this->resetHandle();

        return $this;
    }

    private function openHandle(): void
    {
        if (is_resource($this->fh)) {
            return;
        }

        $this->fh = fopen($this->file, 'r');

        if (!is_resource($this->fh)) {
            throw new \RuntimeException('Unable to open NDJSON file: '.$this->file);
        }

        $this->currentPage = 0;
        $this->nextRowIndex = 0;
    }

    private function closeHandle(): void
    {
        if (is_resource($this->fh)) {
            fclose($this->fh);
        }

        $this->fh = null;
    }

    private function resetHandle(): void
    {
        $this->closeHandle();
        $this->openHandle();
    }

    private function decodeLine(string $line)
    {
        $decoded = json_decode($line, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON found in NDJSON file: '.$this->file.' ('.json_last_error_msg().')');
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('NDJSON line must decode to an object/array: '.$this->file);
        }

        return $decoded;
    }

    private function computeFields(): array
    {
        $fh = fopen($this->file, 'r');

        if (!is_resource($fh)) {
            throw new \RuntimeException('Unable to open NDJSON file: '.$this->file);
        }

        $fields = [];
        try {
            while (($line = fgets($fh)) !== false) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $array = $this->decodeLine($line);
                $dottedArray = array_dot($array);

                $fields = array_merge($fields, array_keys($dottedArray));
            }
        } finally {
            fclose($fh);
        }

        return array_values(array_unique($fields));
    }

    public function getDataRows(int $page = 1, array $fieldsToRetrieve = []): array
    {
        if ($page < 1) {
            $page = 1;
        }

        $desiredOffset = ($page - 1) * $this->perPage;

        $this->openHandle();

        // Optimise for sequential access (as Migrator reads pages 1..N).
        if ($page !== $this->currentPage + 1 || $desiredOffset < $this->nextRowIndex) {
            $this->resetHandle();

            $skipped = 0;
            while ($skipped < $desiredOffset && ($line = fgets($this->fh)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $skipped++;
                $this->nextRowIndex++;
            }
        }

        $dataRows = [];
        $linesRead = 0;

        while ($linesRead < $this->perPage && ($line = fgets($this->fh)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $array = $this->decodeLine($line);
            $dottedArray = array_dot($array);

            $dataRow = new DataRow();

            foreach ($dottedArray as $key => $value) {
                if (in_array($key, $fieldsToRetrieve, true)) {
                    $dataRow->addDataItem(new DataItem($key, $value));
                }
            }

            $dataRows[] = $dataRow;

            $linesRead++;
            $this->nextRowIndex++;
        }

        $this->currentPage = $page;

        return $dataRows;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function countDataRows(): int
    {
        $file = new \SplFileObject($this->file, 'r');
        $count = 0;

        while (!$file->eof()) {
            $line = trim($file->fgets());
            if ($line !== '') {
                $count++;
            }
        }

        return $count;
    }

    public function countPages(): int
    {
        return ceil($this->countDataRows() / $this->perPage);
    }
}
