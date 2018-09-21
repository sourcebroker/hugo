<?php


namespace SourceBroker\Hugo\Queue\Storage;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MySqlStorage implements StorageInterface
{
    const TABLE_NAME = 'queue_items';

    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var Connection
     *
     */
    private $connection;

    public function initializeObject(): void
    {
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);
    }

    /**
     * Removes and returns the first element of the list.
     *
     * @param string $key The key name of list.
     *
     * @return array|null
     */
    public function first($key): ?array
    {
        $qb = $this->connection->createQueryBuilder();
        $result = $qb->select('*')->from(self::TABLE_NAME)
            ->andWhere(
                $qb->expr()->eq('namespace', $qb->createNamedParameter($key, \PDO::PARAM_STR)),
                $qb->expr()->eq('executed', $qb->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->orderBy('created_date', 'ASC')
            ->execute()->fetch();

        if ($result) {
            $this->unsetElement($key, $result['value']);
        }

        return $result ?: null;
    }

    /**
     * @param $key
     * @param $value
     */
    public function unsetElement($key, $value): void
    {
        $qb = $this->connection->createQueryBuilder();

        $now = new \DateTime();
        $qb->update(self::TABLE_NAME)
            ->set('executed', 1)
            ->set('executed_date', $now->format(self::DATE_FORMAT))
            ->andWhere(
                $qb->expr()->eq('namespace', $qb->createNamedParameter($key)),
                $qb->expr()->eq('value', $qb->createNamedParameter($value))
            )
            ->execute();
    }

    /**
     * Removes and returns the last element of the list.
     *
     * @param string $key The key name of list.
     *
     * @return array|null
     */
    public function last($key): ?array
    {
        $qb = $this->connection->createQueryBuilder();

        $result = $qb->select('*')->from(self::TABLE_NAME)
            ->andWhere(
                $qb->expr()->eq('namespace', $qb->createNamedParameter($key, \PDO::PARAM_STR)),
                $qb->expr()->eq('executed', $qb->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->orderBy('created_date', 'DESC')
            ->execute()->fetch();

        if ($result) {
            $this->unsetElement($key, $result['value']);
        }

        return $result ?: null;
    }

    /**
     * Append the value into the end of list.
     *
     * @param string $key The key name of list.
     * @param mixed $value Pushes value of the list.
     */
    public function append($key, $value): void
    {
        $now = new \DateTime();
        $qb = $this->connection->createQueryBuilder();
        $qb->insert(self::TABLE_NAME)
            ->setValue('namespace', $key)
            ->setValue('value', $value)
            ->setValue('created_date', $now->format(self::DATE_FORMAT))
            ->execute();
    }

    /**
     * Prepend the value into the beginning of list.
     *
     * @param string $key The key name of list.
     * @param mixed $value Pushes value of the list.
     */
    public function prepend($key, $value)
    {
        // This method is not supported
    }

    /**
     * Count all elements in a list.
     *
     * @param string $key The key name of list.
     *
     * @return int
     */
    public function count($key): int
    {
        $qb = $this->connection->createQueryBuilder();

        return $qb->count('uid')->from(self::TABLE_NAME)
            ->andWhere(
                $qb->expr()->eq('namespace', $qb->createNamedParameter($key, \PDO::PARAM_STR)),
                $qb->expr()->eq('executed', $qb->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->execute()->fetch()[0];
    }

    /**
     * Unset all element in a list
     *
     * @param string $key
     *
     */
    public function unsetAll($key)
    {
        $now = new \DateTime();
        $qb = $this->connection->createQueryBuilder();

        $qb->update(self::TABLE_NAME)
            ->set('executed', 1)
            ->set('executed_date', $now->format(self::DATE_FORMAT))
            ->where($qb->expr()->eq('namespace', $qb->createNamedParameter($key, \PDO::PARAM_STR)))
            ->execute();
    }
}
