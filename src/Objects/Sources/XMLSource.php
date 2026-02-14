<?php

namespace JordJD\uxdm\Objects\Sources;

use JordJD\uxdm\Interfaces\SourceInterface;
use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;
use DOMDocument;
use DOMXPath;
use Exception;

class XMLSource implements SourceInterface
{
    protected $xpath;
    protected $xpathQuery;
    protected $fields = [];
    protected $perPage = 10;

    public function __construct($file, $xpathQuery)
    {
        $doc = new DOMDocument();
        $doc->load($file);

        $this->xpath = new DOMXPath($doc);
        $this->xpathQuery = $xpathQuery;
    }

    public function addXMLNamespace($prefix, $namespaceURI)
    {
        $this->xpath->registerNamespace($prefix, $namespaceURI);
    }

    private function getXMLFields()
    {
        $fields = [];

        $domNodeList = $this->xpath->query($this->xpathQuery);

        if (!$domNodeList || !$domNodeList->length) {
            throw new Exception('Xpath query is invalid or points to a non-existant DOM element.');
        }

        foreach ($domNodeList as $domElement) {
            foreach ($domElement->childNodes as $childNode) {
                $fields[] = $childNode->nodeName;
            }
        }

        $fields = array_unique($fields);

        return $fields;
    }

    public function getDataRows(int $page = 1, array $fieldsToRetrieve = []): array
    {
        $offset = (($page - 1) * $this->perPage);

        $domNodeList = $this->xpath->query($this->xpathQuery);

        $dataRows = [];

        $count = 0;

        foreach ($domNodeList as $domElement) {
            if ($count >= $offset && $count < $offset + $this->perPage) {
                $dataRow = new DataRow();

                foreach ($domElement->childNodes as $childNode) {
                    if (in_array($childNode->nodeName, $fieldsToRetrieve)) {
                        $dataRow->addDataItem(new DataItem($childNode->nodeName, $childNode->nodeValue));
                    }
                }

                $dataRows[] = $dataRow;
            }

            $count++;
        }

        return $dataRows;
    }

    public function getFields(): array
    {
        if (!$this->fields) {
            $this->fields = array_values($this->getXMLFields());
        }

        return $this->fields;
    }

    public function countDataRows(): int
    {
        $domNodeList = $this->xpath->query($this->xpathQuery);

        return $domNodeList->length;
    }

    public function countPages(): int
    {
        return ceil($this->countDataRows() / $this->perPage);
    }
}
