<?php

namespace SourceBroker\Hugo\Controller;

use SourceBroker\Hugo\Configuration\Configurator;
use SourceBroker\Hugo\Queue\QueueInterface;
use SourceBroker\Hugo\Service\BuildService;
use SourceBroker\Hugo\Service\ExportContentService;
use SourceBroker\Hugo\Service\ExportMediaService;
use SourceBroker\Hugo\Service\ExportPageService;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class AdministrationController
 *
 * @package SourceBroker\Hugo\Controller
 *
 * @property BackendTemplateView $view
 */
class AdministrationController extends ActionController
{

    /**
     * @var int
     */
    protected $pageUid;

    /**
     * Backend Template Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @return void
     */
    public function indexAction(): void
    {
        try {
            $configurator = Configurator::getByPid($this->pageUid);
            $pageTsConfig = $configurator->getConfig();

            $this->view->assignMultiple([
                'pageTsConfig' => $pageTsConfig,
                'mainConfiguration' => $this->getMainConfigurationFromPageTsConfig($pageTsConfig),
            ]);
        } catch (\Exception $e) {
            $this->controllerContext->getFlashMessageQueue()->addMessage(
                GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $e->getMessage() . ' (error code: ' . $e->getCode() . ')',
                    'Error occured when trying to collect configuration',
                    FlashMessage::WARNING
                )
            );
        }
    }

    /**
     * Manual execution of export to hugo
     *
     * @return void
     *
     * @throws UnsupportedRequestTypeException If the request is not a web request
     * @throws StopActionException
     */
    public function exportAction(): void
    {
        $messageContent = '';
        $messageSeverity = FlashMessage::OK;

        try {
            /** @var ExportContentService $ExportContentService */
            $ExportContentService = $this->objectManager->get(ExportContentService::class);

            /** @var ExportPageService $ExportPageService */
            $ExportPageService = $this->objectManager->get(ExportPageService::class);

            /** @var ExportMediaService $ExportMediaService */
            $ExportMediaService = $this->objectManager->get(ExportMediaService::class);

            if (
                $ExportContentService->exportAll()
                && $ExportPageService->exportAll()
                && $ExportMediaService->exportAll()
            ) {
                $messageTitle = 'Exported successfully';
            } else {
                $messageTitle = 'An error occured, please try again';
                $messageContent = 'At least one export could not be finished correctly.';
                $messageSeverity = FlashMessage::WARNING;
            }
        } catch (\Exception $e) {
            $messageTitle = 'An error occurred';
            $messageContent = $e->getMessage();
            $messageSeverity = FlashMessage::ERROR;
        }

        $this->controllerContext->getFlashMessageQueue()->addMessage(
            GeneralUtility::makeInstance(FlashMessage::class, $messageTitle, $messageContent, $messageSeverity, true)
        );

        $this->redirect('index');
    }

    /**
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function exportAjax()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $queue = $objectManager->get(QueueInterface::class);
        $queueItem = $queue->pop();
        if ($queueItem) {
            list($action, $table, $uid) = explode(':', $queueItem['value']);

            if (MathUtility::canBeInterpretedAsInteger($uid)) {
                $uid = (int)$uid;
            }

            switch ($action) {
                case 'update':
                    $this->updateElements($table, $uid);
                    break;
                case 'delete':
                    $this->deleteElements($table, $uid);
                    break;
            }
        }
    }

    /**
     * @param string $tableName
     * @param int $recordId
     *
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    protected function updateElements(string $tableName, int $recordId)
    {
        if ($tableName === 'tt_content') {
            $this->exportHugoContentElements($recordId);
        }

        $this->exportHugoPages();
    }

    /**
     * @param string $tableName
     * @param int $recordId
     *
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    protected function deleteElements(string $tableName, int $recordId)
    {
        if ($tableName === 'tt_content') {
            $this->deleteHugoContentElements($recordId);
        }

        $this->exportHugoPages();
    }

    /**
     * @return void
     *
     * @todo Check system environment - if shell script/executable bin exists etc. Display warning if something is wrong
     */
    public function systemEnvironmentCheckAction()
    {

    }

    /**
     * @return void
     *
     * @todo Folders and files preview of the exported structure
     */
    public function exportedStructurePreviewAction()
    {

    }

    /**
     * @return void
     */
    protected function initializeAction()
    {
        parent::initializeAction();
        $this->pageUid = (int)GeneralUtility::_GET('id');

        if (empty($this->pageUid)) {
            die('Select page on navigation module.');
        }
    }

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);

        if (empty($view)) {
            return;
        }

        $this->createMenu();
    }

    /**
     * Generates and adds the menu to the docheader
     *
     * @return void
     */
    protected function createMenu()
    {
        if (!$this->view instanceof BackendTemplateView) {
            return;
        }

        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();

        $menu->setIdentifier('hugo');
        $menuItems = [
            'Administration' => [
                'index',
//                'exportedStructurePreview',
//                'systemEnvironmentCheck'
            ]
        ];

        foreach ($menuItems as $controller => $actions) {
            $underscoredControllerName = GeneralUtility::camelCaseToLowerCaseUnderscored($controller);
            foreach ($actions as $action) {
                $menuItem = $menu->makeMenuItem();
                $menuItem->setTitle(
                    LocalizationUtility::translate(
                        'LLL:EXT:hugo/Resources/Private/Language/locallang_mod.xlf:menu.' .
                        $underscoredControllerName . '.' . GeneralUtility::camelCaseToLowerCaseUnderscored($action),
                        ''
                    )
                );
                $menuItem->setActive($this->request->getControllerName() === $controller && $this->request->getControllerActionName() === $action)
                    ->setHref($uriBuilder->reset()->uriFor($action, [], $controller));
                $menu->addMenuItem($menuItem);
            }
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * @param array $pageTsConfig
     *
     * @return array
     */
    private function getMainConfigurationFromPageTsConfig(array $pageTsConfig)
    {
        return [
            'Command' => str_replace(['{PATH_site}'], PATH_site, $pageTsConfig['hugo']['command']),
            'Executable bin path' => $pageTsConfig['hugo']['path']['binary'],
        ];
    }

    /**
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \Exception
     */
    protected function exportHugoPages()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $hugoExportPageService = $objectManager->get(ExportPageService::class);
        $hugoExportPageService->exportAll();
        $hugoBuildService = $objectManager->get(BuildService::class);
        $hugoBuildService->buildAll();
    }

    /**
     * @param null $contentRecordUid
     *
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    protected function exportHugoContentElements($contentRecordUid = null)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $hugoExportContentService = $objectManager->get(ExportContentService::class);
        if ($contentRecordUid === null) {
            $hugoExportContentService->exportAll();
        } else {
            $hugoExportContentService->exportSingle($contentRecordUid);
        }
    }

    /**
     * @param int $contentRecordUid
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    protected function deleteHugoContentElements(int $contentRecordUid): void
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $hugoExportContentService = $objectManager->get(ExportContentService::class);
        $hugoExportContentService->deleteSingle($contentRecordUid);
    }
}
