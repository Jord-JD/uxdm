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

    public function __construct($file)
    {
        $this->file = $file;

        $firstCSVLine = $this->getCSVLines(0, 1);
        $this->fields = reset($firstCSVLine);
    }

    protected function getCSVLines($offset, $amount)
    {
        $lines = [];
        $lineCount = 0;
        $fh = fopen($this->file, 'r');

        while (($line = fgetcsv($fh, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            if ($lineCount >= $offset && $lineCount < $offset + $amount) {
                $lines[] = $line;
            }

            if ($lineCount >= $offset + $amount) {
                break;
            }

            $lineCount++;
        }

        return $lines;
    }

    public function getDataRows(int $page = 1, array $fieldsToRetrieve = []): array
    {
        $offset = 1 + (($page - 1) * $this->perPage);

        $lines = $this->getCSVLines($offset, $this->perPage);

        $dataRows = [];

        foreach ($lines as $line) {
            $dataRow = new DataRow();

            foreach ($line as $key => $value) {
                if (array_key_exists($key, $this->fields) && in_array($this->fields[$key], $fieldsToRetrieve)) {
                    $dataRow->addDataItem(new DataItem($this->fields[$key], $value));
                }
            }

            $dataRows[] = $dataRow;
        }

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

        return $this;
    }

    public function setDelimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function setEnclosure(string $enclosure): self
    {
        $this->enclosure = $enclosure;

        return $this;
    }

    public function setEscape(string $escape): self
    {
        $this->escape = $escape;

        return $this;
    }
}
