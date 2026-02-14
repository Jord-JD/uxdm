<?php

namespace JordJD\uxdm\Objects\Exceptions;

use JordJD\uxdm\Objects\DataRow;
use Exception;

class ValidationException extends Exception
{
    private $validationMessages;
    private $dataRow;

    public function __construct(array $validationMessages, DataRow $dataRow)
    {
        $this->validationMessages = $validationMessages;
        $this->dataRow = $dataRow;

        parent::__construct($this->buildExceptionMessage());
    }

    private function buildExceptionMessage(): string
    {
        $message = 'Validation exception: '.PHP_EOL;
        $message .= print_r($this->validationMessages, true).PHP_EOL;
        $message .= 'Data row: '.PHP_EOL;
        $message .= print_r($this->dataRow->toArray(), true).PHP_EOL;

        return $message;
    }

    public function getValidationMessages(): array
    {
        return $this->validationMessages;
    }

    public function getDataRow(): DataRow
    {
        return $this->dataRow;
    }
}
