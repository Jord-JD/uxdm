<?php

namespace JordJD\uxdm\Objects;

use JordJD\CliProgressBar\ProgressBar;
use JordJD\uxdm\Interfaces\DestinationInterface;
use JordJD\uxdm\Interfaces\SourceInterface;
use JordJD\uxdm\Interfaces\TransformerInterface;
use JordJD\uxdm\Objects\Exceptions\MissingFieldToMigrateException;
use JordJD\uxdm\Objects\Exceptions\NoDestinationException;
use JordJD\uxdm\Objects\Exceptions\NoSourceException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class Migrator.
 */
class Migrator
{
    private $source;
    private $destinationContainers = [];
    private $fieldsToMigrate = [];
    private $keyFields = [];
    private $fieldMap = [];
    private $skipIfTrueCheck = null;
    private $validationRules = [];
    private $sourceCachePool;
    private $sourceCacheKey;
    private $sourceCacheExpiresAfter;
    private $showProgressBar = false;
    private $validateBeforeMigrating = false;
    private $transformers = [];

    /**
     * Set the source object to migrate data from.
     *
     * @param SourceInterface $source
     *
     * @return $this
     */
    public function setSource(SourceInterface $source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Set the destination object to migrate data to.
     *
     * The fields you wish to migrate into this destination can be optionally specified.
     * If no fields are specified, it is assumed you wish to migrate all fields set via the `setFieldsToMigrate` method.
     *
     * @param DestinationInterface $destination
     * @param array                $fields
     *
     * @return $this
     */
    public function setDestination(DestinationInterface $destination, array $fields = [])
    {
        $this->destinationContainers = [];
        $this->addDestination($destination, $fields);

        return $this;
    }

    /**
     * Add a destination object to migrate data to. Multiple destination objects can be added using this method.
     *
     * The fields you wish to migrate into this destination can be optionally specified.
     * If no fields are specified, it is assumed you wish to migrate all fields set via the `setFieldsToMigrate` method.
     *
     * @param DestinationInterface $destination
     * @param array                $fields
     *
     * @return $this
     */
    public function addDestination(DestinationInterface $destination, array $fields = [])
    {
        $this->destinationContainers[] = new DestinationContainer($destination, $fields);

        return $this;
    }

    /**
     * Set the fields you wish to migrate from the source.
     *
     * @param array $fieldsToMigrate
     *
     * @return $this
     */
    public function setFieldsToMigrate(array $fieldsToMigrate)
    {
        $this->fieldsToMigrate = $fieldsToMigrate;

        return $this;
    }

    /**
     * Set the fields you consider to be key fields (those fields, when combined, uniquely represent a particular
     * data row). Consider key fields to be similar to the primary keys of a database table.
     *
     * Destination objects will typically use key fields to prevent duplicates when importing multiple data rows and/or
     * running multiple migrations after one another.
     *
     * @param array $keyFields
     *
     * @return $this
     */
    public function setKeyFields(array $keyFields)
    {
        $this->keyFields = $keyFields;

        return $this;
    }

    /**
     * Set the mapping from source fields to destination fields.
     *
     * This field map should be an associated array with the source field name as the keys and the destination field
     * names as the values.
     *
     * Example: [
     *   'name'  => 'full_name',
     *   'email' => 'email_address',
     * ]
     *
     * @param array $fieldMap
     *
     * @return $this
     */
    public function setFieldMap(array $fieldMap)
    {
        $this->fieldMap = $fieldMap;

        return $this;
    }

    /**
     * Adds a data row transformer.
     *
     * A transformer is a class which modifies a data row retrieved from the source, before it is
     * transferred to the destination.
     *
     * @param TransformerInterface $transformer
     *
     * @return $this
     */
    public function addTransformer(TransformerInterface $transformer)
    {
        $this->transformers[] = $transformer;

        return $this;
    }

    /**
     * Set the skip if true check.
     *
     * The skip if true check is a function that is ran on each data row. If it returns true, the data row is skipped
     * during the migration process, and thus not sent to the destination(s).
     *
     * This can be useful to filter out data rows you do not want to be migrated.
     *
     * Example that skips crew members that are not captains:
     *
     * function(DataRow $dataRow) {
     *   $rankDataItem = $dataRow->getDataItemByFieldName('rank');
     *   return $rankDataItem->value !== 'Captain';
     * }
     *
     * @param callable $skipIfTrueCheck
     *
     * @return $this
     */
    public function setSkipIfTrueCheck(callable $skipIfTrueCheck)
    {
        $this->skipIfTrueCheck = $skipIfTrueCheck;

        return $this;
    }

    /**
     * Sets the validation rules for the source data.
     *
     * @see https://github.com/Jord-JD/omega-validator
     *
     * @param array $rules
     *
     * @return $this
     */
    public function setValidationRules(array $rules)
    {
        $this->validationRules = $rules;

        return $this;
    }

    /**
     * Sets the (optional) caching of the source data.
     *
     * This requires a PSR-6 compliant cache item pool object, a cache key, and a cache expiry time in seconds.
     *
     * @param CacheItemPoolInterface $sourceCachePool
     * @param string                 $sourceCacheKey
     * @param int                    $sourceCacheExpiresAfter
     *
     * @return $this
     */
    public function setSourceCache(CacheItemPoolInterface $sourceCachePool, string $sourceCacheKey, int $sourceCacheExpiresAfter = 60 * 60 * 24)
    {
        $this->sourceCachePool = $sourceCachePool;
        $this->sourceCacheKey = $sourceCacheKey;
        $this->sourceCacheExpiresAfter = $sourceCacheExpiresAfter;

        return $this;
    }

    /**
     * Enables output of progress bar during migration. This is useful when running UXDM migrations as part of a
     * command line script.
     *
     * @return $this
     */
    public function withProgressBar()
    {
        $this->showProgressBar = true;

        return $this;
    }

    /**
     * Enables validation of all data rows before the migration begins.
     *
     * @return $this
     */
    public function validateBeforeMigrating()
    {
        $this->validateBeforeMigrating = true;

        return $this;
    }

    /**
     * Retrieves one page of data rows from the source.
     *
     * @param $page
     *
     * @return mixed
     */
    private function getSourceDataRows($page)
    {
        if (!$this->sourceCachePool || !$this->sourceCacheKey) {
            return $this->source->getDataRows($page, $this->fieldsToMigrate);
        }

        $cacheItem = $this->sourceCachePool->getItem(sha1($this->sourceCacheKey.$page));

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $dataRows = $this->source->getDataRows($page, $this->fieldsToMigrate);
        $cacheItem->set($dataRows);
        $cacheItem->expiresAfter($this->sourceCacheExpiresAfter);
        $this->sourceCachePool->save($cacheItem);

        return $dataRows;
    }

    /**
     * Performs a sanity check on the current state of the object, and throws an appropriate explanatory exception if a
     * migration can not be performed.
     *
     * @throws MissingFieldToMigrateException
     * @throws NoDestinationException
     * @throws NoSourceException
     */
    private function sanityCheck()
    {
        if (!$this->source) {
            throw new NoSourceException('No source specified for migration.');
        }

        if (!$this->destinationContainers) {
            throw new NoDestinationException('No destination containers specified for migration.');
        }

        if (!$this->fieldsToMigrate) {
            $this->fieldsToMigrate = $this->source->getFields();
        }

        $postDotFieldsToMigrate = array_map(function ($field) {
            $fieldParts = explode('.', $field);

            return end($fieldParts);
        }, $this->fieldsToMigrate);

        foreach (array_keys($this->fieldMap) as $sourceField) {
            if (!in_array($sourceField, $this->fieldsToMigrate) && !in_array($sourceField, $postDotFieldsToMigrate)) {
                throw new MissingFieldToMigrateException('The source field `'.$sourceField.'` is present in the field map but not present in the fields to migrate.');
            }
        }

        foreach ($this->keyFields as $keyField) {
            if (!in_array($keyField, $this->fieldsToMigrate) && !in_array($keyField, $postDotFieldsToMigrate)) {
                throw new MissingFieldToMigrateException('The field `'.$keyField.'` is present in the key fields list but not present in the fields to migrate.');
            }
        }
    }

    /**
     * Handles the data migration process, involving pulling data from the source, validating and/or manipulating the
     * data if required, and pushing it to one or more specified destinations.
     *
     * @throws MissingFieldToMigrateException
     * @throws NoDestinationException
     * @throws NoSourceException
     */
    public function migrate(): void
    {
        $this->sanityCheck();

        if ($this->validateBeforeMigrating) {
            if ($this->showProgressBar) {
                $progressBar = new ProgressBar();
                $progressBar->setMessage('Validating...');
                $progressBar->setMaxProgress($this->source->countPages() * count($this->destinationContainers));
                $progressBar->display();
            }

            for ($page = 1; $page < PHP_INT_MAX; $page++) {
                $dataRows = $this->getSourceDataRows($page);

                if (!$dataRows) {
                    break;
                }

                foreach ($dataRows as $key => $dataRow) {
                    $dataRow->validate($this->validationRules);
                }

                if (isset($progressBar)) {
                    $progressBar->advance()->display();
                }
            }

            if (isset($progressBar)) {
                $progressBar->complete();
            }
        }

        if ($this->showProgressBar) {
            $progressBar = new ProgressBar();
            $progressBar->setMaxProgress($this->source->countPages() * count($this->destinationContainers));
            $progressBar->display();
        }

        for ($page = 1; $page < PHP_INT_MAX; $page++) {
            $dataRows = $this->getSourceDataRows($page);

            if (!$dataRows) {
                break;
            }

            $this->prepareDataRows($dataRows);
            $this->transformDataRows($dataRows);
            $this->unsetDataRowsToSkip($dataRows);

            foreach ($this->destinationContainers as $destinationContainer) {
                $destinationContainer->putDataRows($dataRows);

                if (isset($progressBar)) {
                    $progressBar->advance()->display();
                }
            }
        }

        foreach ($this->destinationContainers as $destinationContainer) {
            $destinationContainer->finishMigration();
        }

        if (isset($progressBar)) {
            $progressBar->complete();
        }
    }

    /**
     * Prepares an array of data rows for migration.
     *
     * This involves validating the data, assigning the key field flag, mapping their field names, and performing any
     * specified manipulations of the data items.
     *
     * @param $dataRows
     */
    private function prepareDataRows(&$dataRows): void
    {
        foreach ($dataRows as $key => $dataRow) {
            $dataRow->prepare(
                $this->validationRules,
                $this->keyFields,
                $this->fieldMap
            );
        }
    }

    /**
     * Transforms an array of data rows, by passing each data row to each previously added transformer.
     *
     * @param array $dataRows
     */
    private function transformDataRows(array &$dataRows): void
    {
        foreach ($dataRows as $dataRow) {
            foreach ($this->transformers as $transformer) {
                /** @var TransformerInterface $transformer */
                $transformer->transform($dataRow);
            }
        }
    }

    /**
     * Removes any data rows from the passed array, if passing them to skip if true check function returns true.
     *
     * @param array $dataRows
     */
    private function unsetDataRowsToSkip(array &$dataRows): void
    {
        $skipIfTrueCheck = $this->skipIfTrueCheck;

        if (is_callable($skipIfTrueCheck)) {
            foreach ($dataRows as $key => $dataRow) {
                if ($skipIfTrueCheck($dataRow)) {
                    unset($dataRows[$key]);
                }
            }
        }
    }
}
