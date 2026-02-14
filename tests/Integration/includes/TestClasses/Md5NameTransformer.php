<?php

namespace JordJD\uxdm\TestIntegrationClasses;

use JordJD\uxdm\Interfaces\TransformerInterface;
use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;

class Md5NameTransformer implements TransformerInterface
{
    public function transform(DataRow &$dataRow): void
    {
        $dataRow->addDataItem(new DataItem('md5_name', md5($dataRow->getDataItemByFieldName('name')->value)));
    }
}
