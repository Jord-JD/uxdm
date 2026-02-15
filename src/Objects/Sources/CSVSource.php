<?php

namespace JordJD\uxdm\Objects\Sources;

use JordJD\uxdm\Interfaces\SourceInterface;
use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;

class CSVSource implements SourceInterface
{
    protected $file;
    protected $fields = [];
    protected $perPage = 10;

    protected $delimiter = ',';
    protected $enclosure = '"';
    protected $escape = '\\';

    /** @var resource|null */
    protected $fh;
    protected $currentPage = 0;
    protected $nextDataRowIndex = 0;

    public function __construct($file)
    {
        $this->file = $file;

        $this->resetHandle();
        $this->fields = $this->readHeaderRow();
    }

    public function __destruct()
    {
        $this->closeHandle();
    }

    private function openHandle(): void
    {
        if (is_resource($this->fh)) {
            return;
        }

        $this->fh = fopen($this->file, 'r');

        if (!is_resource($this->fh)) {
            throw new \RuntimeException('Unable to open CSV file: '.$this->file);
        }

        $this->currentPage = 0;
        $this->nextDataRowIndex = 0;
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

    private function readHeaderRow(): array
    {
        $this->openHandle();

        $header = fgetcsv($this->fh, 0, $this->delimiter, $this->enclosure, $this->escape);

        return is_array($header) ? $header : [];
    }

    public function getDataRows(int $page = 1, array $fieldsToRetrieve = []): array
    {
        if ($page < 1) {
            $page = 1;
        }

        $desiredOffset = ($page - 1) * $this->perPage;

        // If pages are requested sequentially (as Migrator does), keep reading from the current file position
        // to avoid re-parsing the CSV from the start for every page.
        if ($page !== $this->currentPage + 1 || $desiredOffset < $this->nextDataRowIndex) {
            $this->resetHandle();
            $this->fields = $this->readHeaderRow();

            $skipped = 0;
            while ($skipped < $desiredOffset && ($line = fgetcsv($this->fh, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
                $skipped++;
                $this->nextDataRowIndex++;
            }
        }

        $dataRows = [];

        $linesRead = 0;
        while ($linesRead < $this->perPage && ($line = fgetcsv($this->fh, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            $dataRow = new DataRow();

            foreach ($line as $key => $value) {
                if (array_key_exists($key, $this->fields) && in_array($this->fields[$key], $fieldsToRetrieve)) {
                    $dataRow->addDataItem(new DataItem($this->fields[$key], $value));
                }
            }

            $dataRows[] = $dataRow;

            $linesRead++;
            $this->nextDataRowIndex++;
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
        $file->seek(PHP_INT_MAX);

        return $file->key();
    }

    public function countPages(): int
    {
        return ceil($this->countDataRows() / $this->perPage);
    }

    public function setPerPage(int $perPage): self
    {
        $this->perPage = $perPage;
        $this->resetHandle();
        $this->fields = $this->readHeaderRow();

        return $this;
    }

    public function setDelimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter;
        $this->resetHandle();
        $this->fields = $this->readHeaderRow();

        return $this;
    }

    public function setEnclosure(string $enclosure): self
    {
        $this->enclosure = $enclosure;
        $this->resetHandle();
        $this->fields = $this->readHeaderRow();

        return $this;
    }

    public function setEscape(string $escape): self
    {
        $this->escape = $escape;
        $this->resetHandle();
        $this->fields = $this->readHeaderRow();

        return $this;
    }
}
