<?php

namespace JordJD\uxdm\Objects\Sources;

use JordJD\uxdm\Interfaces\SourceInterface;
use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;

class JSONFilesSource implements SourceInterface
{
    protected $files;
    protected $fields = [];
    protected $perPage = 10;

    public function __construct(array $files = [])
    {
        $this->files = $files;

        $this->fields = [];
        $this->fields[] = '_file';

        foreach ($files as $file) {
            $array = json_decode(file_get_contents($file), true);
            $dottedArray = array_dot($array);

            $this->fields = array_merge($this->fields, array_keys($dottedArray));
        }

        $this->fields = array_unique($this->fields);
    }

    public function getDataRows(int $page = 1, array $fieldsToRetrieve = []): array
    {
        $offset = 0 + (($page - 1) * $this->perPage);

        $files = array_slice($this->files, $offset, $this->perPage);

        $dataRows = [];

        foreach ($files as $file) {
            $dataRow = new DataRow();

            if (in_array('_file', $fieldsToRetrieve)) {
                $dataRow->addDataItem(new DataItem('_file', $file));
            }

            $array = json_decode(file_get_contents($file), true);
            $dottedArray = array_dot($array);

            foreach ($dottedArray as $key => $value) {
                if (in_array($key, $fieldsToRetrieve)) {
                    $dataRow->addDataItem(new DataItem($key, $value));
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
        return count($this->files);
    }

    public function countPages(): int
    {
        return ceil($this->countDataRows() / $this->perPage);
    }
}
