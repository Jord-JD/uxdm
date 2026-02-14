<?php

namespace JordJD\uxdm\Interfaces;

use JordJD\uxdm\Objects\DataRow;

interface TransformerInterface
{
    public function transform(DataRow &$dataRow): void;
}
