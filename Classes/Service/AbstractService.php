<?php

/***************************************************************
 *  Copyright notice
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace SourceBroker\Hugo\Service;

use SourceBroker\Hugo\Domain\Model\ServiceResult;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AbstractService
 *
 */
abstract class AbstractService
{

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var bool
     */
    protected $locked = false;

    /**
     * @var \TYPO3\CMS\Core\Locking\LockingStrategyInterface
     */
    protected $locker;

    /**
     * @var \TYPO3\CMS\Core\Locking\LockFactory
     */
    protected $lockFactory;

    /**
     * AbstractService constructor.
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->lockFactory = $this->objectManager->get(LockFactory::class);
    }

    /**
     * @param string $id ID to identify this lock in the system
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    protected function createLocker($id)
    {
        $this->locker = $this->lockFactory->createLocker(
            $id,
            LockingStrategyInterface::LOCK_CAPABILITY_SHARED | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
        );
        do {
            try {
                $this->locked = $this->locker->acquire(LockingStrategyInterface::LOCK_CAPABILITY_SHARED | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK);
            } catch (LockAcquireWouldBlockException $e) {
                usleep(100000); //100ms
                continue;
            }
            if ($this->locked) {
                break;
            }
        } while (true);
    }

    /**
     * @return \SourceBroker\Hugo\Domain\Model\ServiceResult
     */
    protected function release(): ServiceResult
    {
        if ($this->locked) {
            $this->locker->release();
            $this->locker->destroy();
        }

        return $this->createServiceResult();
    }

    /**
     * @return \SourceBroker\Hugo\Domain\Model\ServiceResult
     */
    protected function createServiceResult(): ServiceResult
    {
        return GeneralUtility::makeInstance(ServiceResult::class);
    }

    /**
     * @param \SourceBroker\Hugo\Domain\Model\ServiceResult $serviceResult
     */
    protected function executeServiceResultCommand(ServiceResult $serviceResult)
    {
        $output = null;
        $return_var = null;
        exec($serviceResult->getCommand(), $output, $return_var);
        if (count($output)) {
            $serviceResult->setCommandOutput(implode("\n", $output));
        }
        if ($return_var == 0) {
            $serviceResult->setExecutedSuccessfully(true);
        }
    }
}
