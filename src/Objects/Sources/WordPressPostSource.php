<?php

namespace JordJD\uxdm\Objects\Sources;

use JordJD\uxdm\Interfaces\SourceInterface;
use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;
use PDO;
use PDOStatement;

class WordPressPostSource implements SourceInterface
{
    protected $pdo;
    protected $fields = [];
    protected $postType;
    protected $perPage = 10;
    protected $prefix = 'wp_';

    public function __construct(PDO $pdo, $postType = 'post')
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
        $this->postType = $postType;
        $this->fields = $this->getPostFields();
    }

    public function setTablePrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    private function getPostFields()
    {
        $sql = $this->getPostSQL(['*']);

        $stmt = $this->pdo->prepare($sql);
        $this->bindLimitParameters($stmt, 0, 1);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $postFields = array_keys($row);

        foreach ($postFields as $key => $postField) {
            $postFields[$key] = $this->prefix.'posts.'.$postField;
        }

        $sql = $this->getPostMetaSQL($row['ID']);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $postMetaFields = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $postMetaFields[] = $this->prefix.'postmeta.'.$row['meta_key'];
        }

        return array_merge($postFields, $postMetaFields);
    }

    private function getPostSQL($fieldsToRetrieve)
    {
        foreach ($fieldsToRetrieve as $key => $fieldToRetrieve) {
            if (strpos($fieldToRetrieve, $this->prefix.'posts.') !== 0 && $fieldToRetrieve !== '*') {
                unset($fieldsToRetrieve[$key]);
            }
        }

        $fieldsSQL = implode(', ', $fieldsToRetrieve);

        $sql = 'select '.$fieldsSQL.' from '.$this->prefix.'posts where post_type = \''.$this->postType.'\'';
        $sql .= ' limit ? , ?';

        return $sql;
    }

    private function getPostMetaSQL($postID, ?array $fieldsToRetrieve = null)
    {
        $sql = 'select meta_key, meta_value from '.$this->prefix.'postmeta where ';

        $sql .= 'post_id = '.$postID;

        if ($fieldsToRetrieve) {
            foreach ($fieldsToRetrieve as $key => $fieldToRetrieve) {
                if (strpos($fieldToRetrieve, $this->prefix.'postmeta.') !== 0) {
                    unset($fieldsToRetrieve[$key]);
                }
                $fieldsToRetrieve[$key] = str_replace($this->prefix.'postmeta.', '', $fieldToRetrieve);
            }

            $sql .= ' and ( ';
            foreach ($fieldsToRetrieve as $fieldToRetrieve) {
                $sql .= ' meta_key = \''.$fieldToRetrieve.'\' or ';
            }
            $sql = substr($sql, 0, -3);
            $sql .= ' ) ';
        }

        return $sql;
    }

    private function bindLimitParameters(PDOStatement $stmt, $offset, $perPage)
    {
        $stmt->bindValue(1, $offset, PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    }

    public function getDataRows(int $page = 1, array $fieldsToRetrieve = []): array
    {
        $offset = (($page - 1) * $this->perPage);

        $postsSql = $this->getPostSQL($fieldsToRetrieve);

        $postsStmt = $this->pdo->prepare($postsSql);
        $this->bindLimitParameters($postsStmt, $offset, $this->perPage);

        $postsStmt->execute();

        $dataRows = [];

        while ($postsRow = $postsStmt->fetch(PDO::FETCH_ASSOC)) {
            $dataRow = new DataRow();

            foreach ($postsRow as $key => $value) {
                $dataRow->addDataItem(new DataItem($this->prefix.'posts.'.$key, $value));
            }

            if (isset($postsRow['ID'])) {
                $postMetaSql = $this->getPostMetaSQL($postsRow['ID'], $fieldsToRetrieve);

                $postMetaStmt = $this->pdo->prepare($postMetaSql);
                $postMetaStmt->execute();

                while ($postMetaRow = $postMetaStmt->fetch(PDO::FETCH_ASSOC)) {
                    $dataRow->addDataItem(new DataItem($this->prefix.'postmeta.'.$postMetaRow['meta_key'], $postMetaRow['meta_value']));
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
        $sql = $this->getPostSQL([]);
        $fromPos = stripos($sql, 'from');
        $limitPos = strripos($sql, 'limit');
        $sqlSuffix = substr($sql, $fromPos, $limitPos - $fromPos);

        $sql = 'select count(*) as count '.$sqlSuffix;

        $countStmt = $this->pdo->prepare($sql);
        $countStmt->execute();

        $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);

        return $countRow['count'];
    }

    public function countPages(): int
    {
        return ceil($this->countDataRows() / $this->perPage);
    }
}
