<?php

namespace JordJD\uxdm\Objects\Sources;

use JordJD\uxdm\Interfaces\SourceInterface;
use JordJD\uxdm\Objects\DataItem;
use JordJD\uxdm\Objects\DataRow;
use PDO;
use PDOStatement;

class WordPressUserSource implements SourceInterface
{
    protected $pdo;
    protected $fields = [];
    protected $perPage = 10;
    protected $prefix = 'wp_';

    public function __construct(PDO $pdo)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
        $this->fields = $this->getUserFields();
    }

    public function setTablePrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Sets how many users are retrieved per page. Default is 10.
     */
    public function setPerPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    private function getUserFields()
    {
        $sql = $this->getUserSQL(['*']);

        $stmt = $this->pdo->prepare($sql);
        $this->bindLimitParameters($stmt, 0, 1);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $userFields = array_keys($row);

        foreach ($userFields as $key => $userField) {
            $userFields[$key] = $this->prefix.'users.'.$userField;
        }

        $sql = $this->getUserMetaSQL($row['ID']);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $userMetaFields = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $userMetaFields[] = $this->prefix.'usermeta.'.$row['meta_key'];
        }

        return array_merge($userFields, $userMetaFields);
    }

    private function getUserSQL($fieldsToRetrieve)
    {
        foreach ($fieldsToRetrieve as $key => $fieldToRetrieve) {
            if (strpos($fieldToRetrieve, $this->prefix.'users.') !== 0 && $fieldToRetrieve !== '*') {
                unset($fieldsToRetrieve[$key]);
            }
        }

        $fieldsSQL = implode(', ', $fieldsToRetrieve);

        $sql = 'select '.$fieldsSQL.' from '.$this->prefix.'users';
        $sql .= ' limit ? , ?';

        return $sql;
    }

    private function getUserMetaSQL($userID, ?array $fieldsToRetrieve = null)
    {
        $sql = 'select meta_key, meta_value from '.$this->prefix.'usermeta where ';

        $sql .= 'user_id = '.$userID;

        if ($fieldsToRetrieve) {
            foreach ($fieldsToRetrieve as $key => $fieldToRetrieve) {
                if (strpos($fieldToRetrieve, $this->prefix.'usermeta.') !== 0) {
                    unset($fieldsToRetrieve[$key]);
                }
                $fieldsToRetrieve[$key] = str_replace($this->prefix.'usermeta.', '', $fieldToRetrieve);
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

        $usersSql = $this->getUserSQL($fieldsToRetrieve);

        $usersStmt = $this->pdo->prepare($usersSql);
        $this->bindLimitParameters($usersStmt, $offset, $this->perPage);

        $usersStmt->execute();

        $dataRows = [];

        while ($usersRow = $usersStmt->fetch(PDO::FETCH_ASSOC)) {
            $dataRow = new DataRow();

            foreach ($usersRow as $key => $value) {
                $dataRow->addDataItem(new DataItem($this->prefix.'users.'.$key, $value));
            }

            if (isset($usersRow['ID'])) {
                $userMetaSql = $this->getUserMetaSQL($usersRow['ID'], $fieldsToRetrieve);

                $userMetaStmt = $this->pdo->prepare($userMetaSql);
                $userMetaStmt->execute();

                while ($userMetaRow = $userMetaStmt->fetch(PDO::FETCH_ASSOC)) {
                    $dataRow->addDataItem(new DataItem($this->prefix.'usermeta.'.$userMetaRow['meta_key'], $userMetaRow['meta_value']));
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
        $sql = $this->getUserSQL([]);
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
