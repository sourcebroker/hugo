<?php
namespace SourceBroker\Hugo\Indexer;

use SourceBroker\Hugo\Domain\Model\Document;
use SourceBroker\Hugo\Domain\Model\DocumentCollection;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Class PageIndexer
 *
 * @package SourceBroker\Hugo\Indexer
 */
class PageIndexer extends AbstractIndexer
{
    /**
     * @param int $pageUid
     * @param Document $document
     *
     * @return array
     */
    public function run(int $pageUid, Document $document): array
    {
        $row = $this->getPageByUid($pageUid);

        $document->setId($row['uid'])
            ->setTitle($row['title'])
            ->setSlug($this->slugify($row['nav_title'] ?: $row['title']))
            ->setDraft(!empty($row['hidden']))
            ->setDeleted(!empty($row['deleted']))
            ->setLayout('main') // Just to test then change with sliding $row['backend_layout'] / $row['backend_layout_next_level']
            ->setRoot(!empty($row['is_siteroot']))
        ;

        if(empty($row['tx_hugo_menuid'])) {
            $parentRow = $this->getPageByUid($row['pid']);
            $menuUid = empty($parentRow['tx_hugo_menuid']) ? '' : $parentRow['tx_hugo_menuid'];
        } else {
            $menuUid = $row['tx_hugo_menuid'];
        }
        $document->addToMenu($menuUid, $row);

        return [
            $pageUid,
            $document,
        ];
    }

    public function runCollection(int $pageUid, DocumentCollection $documentCollection): array
    {

    }

    /**
     * @param int $uid
     *
     * @return array
     */
    protected function getPageByUid(int $uid): array
    {
        return $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            'pages',
            'uid = '.(int)$uid
        );
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

}
