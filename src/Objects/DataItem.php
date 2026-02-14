<?php

namespace JordJD\uxdm\Objects;

class DataItem
{
    public $fieldName;
    public $value;
    public $keyField = false;

    public function __construct($fieldName, $value = '', $keyField = false)
    {
        $this->fieldName = $fieldName;
        $this->value = $value;
        $this->keyField = $keyField;
    }
}
