<?php

namespace JordJD\uxdm\TestIntegrationClasses;

use JordJD\uxdm\Interfaces\TransformerInterface;
use JordJD\uxdm\Objects\DataRow;

class UppercaseNameTransformer implements TransformerInterface
{
    public function transform(DataRow &$dataRow): void
    {
        $dataItem = $dataRow->getDataItemByFieldName('name');

        if ($dataItem) {
            $dataItem->value = strtoupper($dataItem->value);
        }
    }
}
