<?php

namespace JordJD\uxdm\Objects;

use JordJD\OmegaValidator\Validator;
use JordJD\uxdm\Objects\Exceptions\NoDataItemsInDataRowException;
use JordJD\uxdm\Objects\Exceptions\ValidationException;

class DataRow
{
    private $dataItems = [];

    public function addDataItem(DataItem $dataItem)
    {
        $this->dataItems[] = $dataItem;
    }

    public function removeDataItem(DataItem $dataItemToDelete)
    {
        foreach ($this->dataItems as $key => $dataItem) {
            if ($dataItem == $dataItemToDelete) {
                unset($this->dataItems[$key]);
                break;
            }
        }
    }

    public function getDataItems()
    {
        return $this->dataItems;
    }

    public function toArray()
    {
        $array = [];
        foreach ($this->dataItems as $dataItem) {
            $array[$dataItem->fieldName] = $dataItem->value;
        }

        return $array;
    }

    public function getDataItemByFieldName(string $fieldName)
    {
        foreach ($this->dataItems as $dataItem) {
            if ($dataItem->fieldName == $fieldName) {
                return $dataItem;
            }
        }
    }

    public function getKeyDataItems()
    {
        $keyDataItems = [];

        foreach ($this->dataItems as $dataItem) {
            if ($dataItem->keyField) {
                $keyDataItems[] = $dataItem;
            }
        }

        return $keyDataItems;
    }

    public function prepare(array $validationRules, array $keyFields, array $fieldMap)
    {
        $this->validate($validationRules);
        $this->setKeyFields($keyFields);
        $this->mapFields($fieldMap);
    }

    public function validate(array $validationRules)
    {
        if (!$this->dataItems) {
            throw new NoDataItemsInDataRowException('Data row contains no data items. The specified source may be producing an invalid data row.');
        }

        if ($validationRules) {
            $validator = new Validator($this->toArray(), $validationRules);
            if ($validator->fails()) {
                throw new ValidationException($validator->messages(), $this);
            }
        }
    }

    private function setKeyFields(array $keyFields)
    {
        foreach ($this->dataItems as $key => $dataItem) {
            if (in_array($dataItem->fieldName, $keyFields)) {
                $dataItem->keyField = true;
                $dataItems[$key] = $dataItem;
            }
        }
    }

    private function mapFields(array $fieldMap)
    {
        foreach ($this->dataItems as $key => $dataItem) {
            if (array_key_exists($dataItem->fieldName, $fieldMap)) {
                $newFieldName = $fieldMap[$dataItem->fieldName];
                $dataItem->fieldName = $newFieldName;
                $dataItems[$key] = $dataItem;
            }
        }
    }
}
