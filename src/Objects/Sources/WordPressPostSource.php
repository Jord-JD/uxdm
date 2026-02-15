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
    protected $termTaxonomies = [];
    protected $termsSeparator = ',';

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

    /**
     * Sets how many posts are retrieved per page. Default is 10.
     */
    public function setPerPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Include post terms/taxonomies (e.g. "category", "post_tag") in the returned fields and data rows.
     *
     * Values are returned as a separator-delimited string of term slugs for each taxonomy.
     *
     * @param array $taxonomies
     */
    public function withTerms(array $taxonomies = ['category', 'post_tag']): self
    {
        $this->termTaxonomies = $taxonomies;

        // Ensure getFields() reflects the requested term taxonomies.
        $this->fields = $this->getPostFields();

        return $this;
    }

    /**
     * Sets the separator used when concatenating multiple term slugs into a single field value.
     */
    public function setTermsSeparator(string $separator): self
    {
        $this->termsSeparator = $separator;

        return $this;
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

        $fields = array_merge($postFields, $postMetaFields);

        foreach ($this->termTaxonomies as $taxonomy) {
            $fields[] = $this->prefix.'terms.'.$taxonomy;
        }

        return $fields;
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

    private function getPostTermsSQL($postID, array $taxonomies): string
    {
        $sql = 'select t.slug, tt.taxonomy';
        $sql .= ' from '.$this->prefix.'term_relationships tr';
        $sql .= ' join '.$this->prefix.'term_taxonomy tt on tr.term_taxonomy_id = tt.term_taxonomy_id';
        $sql .= ' join '.$this->prefix.'terms t on tt.term_id = t.term_id';
        $sql .= ' where tr.object_id = '.((int) $postID);

        if ($taxonomies) {
            $taxonomies = array_map(function ($taxonomy) {
                return '\''.str_replace('\'', '\\\'', $taxonomy).'\'';
            }, $taxonomies);
            $sql .= ' and tt.taxonomy in ('.implode(', ', $taxonomies).')';
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

                if ($this->termTaxonomies) {
                    $termsSql = $this->getPostTermsSQL($postsRow['ID'], $this->termTaxonomies);
                    $termsStmt = $this->pdo->prepare($termsSql);
                    $termsStmt->execute();

                    $termsByTaxonomy = [];
                    while ($termsRow = $termsStmt->fetch(PDO::FETCH_ASSOC)) {
                        $taxonomy = $termsRow['taxonomy'] ?? null;
                        $slug = $termsRow['slug'] ?? null;

                        if ($taxonomy && $slug) {
                            $termsByTaxonomy[$taxonomy][] = $slug;
                        }
                    }

                    foreach ($this->termTaxonomies as $taxonomy) {
                        $fieldName = $this->prefix.'terms.'.$taxonomy;

                        // Respect requested fields (when caller filters).
                        if ($fieldsToRetrieve && !in_array($fieldName, $fieldsToRetrieve, true)) {
                            continue;
                        }

                        $slugs = $termsByTaxonomy[$taxonomy] ?? [];
                        $value = implode($this->termsSeparator, $slugs);

                        $dataRow->addDataItem(new DataItem($fieldName, $value));
                    }
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
