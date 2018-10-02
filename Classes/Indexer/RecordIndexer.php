<?php

namespace SourceBroker\Hugo\Indexer;

use SourceBroker\Hugo\Configuration\Configurator;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;
use SourceBroker\Hugo\Service\RteService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class RecordIndexer
 */
class RecordIndexer extends AbstractIndexer implements SingletonInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var array
     */
    private $frameworkConfiguration;

    /**
     * @var DataMapper
     */
    private $dataMapper;

    /**
     * @var RteService
     */
    private $rteService;

    /**
     * RecordIndexer constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->frameworkConfiguration = $this->objectManager->get(ConfigurationManagerInterface::class)
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
    }

    /**
     * @param int $pageUid
     * @param DocumentCollection $documentCollection
     * @return array
     */
    public function getDocumentsForPage(int $pageUid, DocumentCollection $documentCollection): array
    {
        $hugoConfig = Configurator::getByPid($pageUid);
        if (!empty($hugoConfig->getOption('record.indexer.exporter')) && is_array($hugoConfig->getOption('record.indexer.exporter'))) {
            foreach ($hugoConfig->getOption('record.indexer.exporter') as $exporterConfig) {
                if ($pageUid == $exporterConfig['pageUid']) {
                    $table = $exporterConfig['table'];
                    $recordsPid = $exporterConfig['recordsPid'];

                    $repository = $this->getRepositoryByTable($table);

                    if (is_object($repository)) {
                        $query = $repository->createQuery();
                        $query->getQuerySettings()->setRespectStoragePage(false);

                        //find with extbase relations
                        $recordRows = $query->matching($query->equals('pid', $recordsPid))->execute()->toArray();
                    } else {
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                        $queryBuilder->select('*')->from($table)->where(
                            $queryBuilder->expr()->eq('pid',
                                $queryBuilder->createNamedParameter($recordsPid, \PDO::PARAM_INT))
                        );
                        $recordRows = $queryBuilder->execute()->fetchAll();
                    }

                    foreach ($recordRows as $record) {
                        if (is_object($record) && $record instanceof AbstractDomainObject) {
                            $record = $this->mapPropertiesToArrayRecursive($record->_getProperties());
                        }

                        $this->parseFields($table, $record, $hugoConfig);

                        // @todo make slug configurable and use same approach in \SourceBroker\Hugo\Typolink\DatabaseRecordLinkBuilder::build
                        $slug = $this->slugify($record['title']);
                        $document = $documentCollection->create();
                        $document->setStoreFilename($record['uid'] . '_' . ucfirst($slug))
                            ->setId($record['uid'])
                            ->setSlug($slug)
                            ->setCustomFields([
                                'template' => $exporterConfig['template'] ?? 'default',
                                'record' => $record,
                            ]);
                    }
                }
            }
        }
        return [
            $pageUid,
            $documentCollection
        ];
    }

    /**
     * @param string $tableName
     *
     * @return Repository|null
     */
    protected function getRepositoryByTable($tableName)
    {
        $repositoryClass = null;
        $modelSubclasses = [];
        foreach ($this->frameworkConfiguration['persistence']['classes'] as $class => $classConfig) {
            if ($tableName == $this->getDataMapper()->convertClassNameToTableName($class)) {
                $tempRepositoryClass = str_replace('Model', 'Repository', $class) . 'Repository';
                if (class_exists($tempRepositoryClass) && !in_array($class, $modelSubclasses)) {
                    $repositoryClass = $tempRepositoryClass;

                    if ($classConfig['subclasses']) {
                        $modelSubclasses = array_merge($modelSubclasses, $classConfig['subclasses']);
                    }
                }
            }
        }

        return $repositoryClass ? $this->objectManager->get($repositoryClass) : null;
    }

    protected function mapPropertiesToArrayRecursive(array $properties)
    {
        foreach ($properties as $property => $value) {
            if (is_object($value)) {
                if ($value instanceof LazyObjectStorage || $value instanceof ObjectStorage) {
                    $properties[$property] = $this->mapPropertiesToArrayRecursive($value->toArray());
                } else {
                    if ($value instanceof AbstractDomainObject) {
                        $properties[$property] = $this->mapPropertiesToArrayRecursive($value->_getProperties());
                    } else {
                        $properties[$property] = $value;
                    }
                }
            }

            if (empty($properties[$property])) {
                $properties[$property] = '';
            }
        }

        return $properties;
    }

    /**
     * @param string $table
     * @param array $record
     * @param Configurator $configurator
     *
     * @return void
     */
    private function parseFields(string $table, array &$record, Configurator $configurator)
    {
        foreach ($record as $columnName => &$columnValue) {
            if (empty($GLOBALS['TCA'][$table]['columns'][$columnName]['config'])) {
                continue;
            }

            $columnTca = $GLOBALS['TCA'][$table]['columns'][$columnName]['config'];
            $recordType = BackendUtility::getTCAtypeValue($table, $record);
            $columnsOverridesConfigOfField = $GLOBALS['TCA'][$table]['types'][$recordType]['columnsOverrides'][$columnName]['config'] ?? null;
            if ($columnsOverridesConfigOfField) {
                ArrayUtility::mergeRecursiveWithOverrule($columnTca, $columnsOverridesConfigOfField);
            }

            $this->processSingleField($columnTca, $columnValue, $configurator);
        }
    }

    /**
     * @param array $columnTca
     * @param $columnValue
     * @param Configurator $configurator
     *
     * @return void
     */
    private function processSingleField(array $columnTca, &$columnValue, Configurator $configurator)
    {
        foreach ($this->getProcessorsFromTca($columnTca) as $processor) {
            switch ($processor) {
                case 'rte':
                    $columnValue = $this->getRteService()->parse($columnValue, $configurator);
                    break;
            }
        }
    }

    /**
     * @param array $columnTca
     *
     * @return array
     */
    private function getProcessorsFromTca(array $columnTca): array
    {
        $processors = [];

        if (!empty($columnTca['enableRichtext'])) {
            $processors[] = 'rte';
        }

        return $processors;
    }

    /**
     * @return DataMapper
     */
    private function getDataMapper(): DataMapper
    {
        if (!$this->dataMapper instanceof DataMapper) {
            $this->dataMapper = $this->objectManager->get(DataMapper::class);
        }

        return $this->dataMapper;
    }

    /**
     * @return RteService
     */
    private function getRteService(): RteService
    {
        if (!$this->rteService instanceof RteService) {
            $this->rteService = $this->objectManager->get(RteService::class);
        }

        return $this->rteService;
    }
}
