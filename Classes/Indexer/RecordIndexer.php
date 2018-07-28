<?php

namespace SourceBroker\Hugo\Indexer;

use SourceBroker\Hugo\Configuration\Configurator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class RecordIndexer extends AbstractIndexer
{
    /**
     * @param int $pageUid
     * @param DocumentCollection $documentCollection
     * @return array
     */
    public function getDocumentsForPage(int $pageUid, DocumentCollection $documentCollection): array
    {
        $hugoConfig = Configurator::getByPid($pageUid);
        if (!empty($hugoConfig->getOption('record.indexer.exporter'))) {
            foreach ($hugoConfig->getOption('record.indexer.exporter') as $exporterConfig) {
                if ($pageUid == $exporterConfig['pageUid']) {
                    $table = $exporterConfig['table'];
                    $recordsPid = $exporterConfig['recordsPid'];

                    /** @var \TYPO3\CMS\Extbase\Persistence\Repository $repository */
                    $repository = $this->getRepositoryByTable($table);

                    if(is_object($repository)) {
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

                    $taxonomy = [];
                    if(array_key_exists('taxonomyMap', $exporterConfig)){
                        foreach($exporterConfig['taxonomyMap'] as $mappedTaxonomyKey => $mappedTaxonomyField) {
                            $taxonomy[$mappedTaxonomyKey] = [];
                        }
                    }

                    foreach ($recordRows as $record) {
                        if(is_object($record) && $record instanceof AbstractDomainObject){
                            $record = $this->mapPropertiesToArrayRecursive($record->_getProperties(), $exporterConfig['mapper']);
                        }

                        if(array_key_exists('taxonomyMap', $exporterConfig)){
                            foreach($exporterConfig['taxonomyMap'] as $mappedTaxonomyKey => $mappedTaxonomyField){
                                if(array_key_exists($mappedTaxonomyKey, $record)){

                                    if(is_array($record[$mappedTaxonomyKey])){
                                        foreach($record[$mappedTaxonomyKey] as $item){
                                            if(array_key_exists($mappedTaxonomyField, $item)) {
                                                if(!in_array($item[$mappedTaxonomyField], $taxonomy[$mappedTaxonomyKey])) {
                                                    $taxonomy[$mappedTaxonomyKey][] = $item[$mappedTaxonomyField];
                                                }
                                            }
                                        }
                                    } else if(!in_array($record[$mappedTaxonomyKey], $taxonomy[$mappedTaxonomyKey])) {
                                        $taxonomy[$mappedTaxonomyKey][] = $record[$mappedTaxonomyKey];
                                    }
                                }
                            }
                        }

                        $slug = $this->slugify($record['title']);
                        $document = $documentCollection->create();
                        $document->setStoreFilename($record['uid'] . '_' . ucfirst($slug))
                            ->setId($record['uid'])
                            ->setTitle($record['title'])
                            ->setSlug($slug)
                            ->setCustomFields(['record' => $record]);

                        foreach($taxonomy as $taxonomyKey => $values){
                            $document->setCustomFields([$taxonomyKey => $values]);
                        }
                    }
                }
            }
        }
        return [
            $pageUid,
            $documentCollection
        ];
    }

    protected function getRepositoryByTable($tableName)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $objectManager->get(ConfigurationManagerInterface::class);
        $frameworkConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $dataMapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);

        $repositoryClass = null;
        $modelSubclasses = [];
        foreach($frameworkConfiguration['persistence']['classes'] as $class => $classConfig){
            if($tableName == $dataMapper->convertClassNameToTableName($class)){
                $tempRepositoryClass = str_replace('Model', 'Repository', $class).'Repository';
                if (class_exists($tempRepositoryClass) && !in_array($class, $modelSubclasses)) {
                    $repositoryClass = $tempRepositoryClass;

                    if($classConfig['subclasses']){
                        $modelSubclasses = array_merge($modelSubclasses, $classConfig['subclasses']);
                    }
                }
            }
        }

        if(!empty($repositoryClass)) {
            return $objectManager->get($repositoryClass);
        }

        return null;
    }

    protected function mapPropertiesToArrayRecursive(array $properties)
    {
        foreach($properties as $property => $value)
        {
            if(is_object($value)) {

                if($value instanceof LazyObjectStorage || $value instanceof ObjectStorage){
                    $properties[$property] = $this->mapPropertiesToArrayRecursive($value->toArray());
                } else if($value instanceof AbstractDomainObject){
                    $properties[$property] = $this->mapPropertiesToArrayRecursive($value->_getProperties());
                } else {
                    $properties[$property] = $value;
                }

            }

            if(empty($properties[$property])){
                $properties[$property] = '';
            }
        }

        return $properties;
    }
}