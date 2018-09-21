<?php

namespace SourceBroker\Hugo\Queue;

use SourceBroker\Hugo\Queue\Storage\StorageInterface;

class SimpleQueue implements QueueInterface
{
    private $name = 'hugo';

    /**
     * @var StorageInterface
     *
     */
    private $storage;

    /**
     * @param StorageInterface $storage
     */
    public function injectStorage(\SourceBroker\Hugo\Queue\Storage\StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * push to queue at the last place new value in format:
     * action:table:uid
     * action and table is required as string
     * uid is optional and should be integer
     *
     * @param string $value
     *
     * @return void
     */
    public function push($value): void
    {
        $this->storage->append($this->name, $value);
    }

    /**
     * pop the first values from queue
     *
     * @return mixed
     */
    public function pop()
    {
        return $this->storage->first($this->name);
    }

    /**
     * returns count of executeable items in queue
     *
     * @return int
     */
    public function count(): int
    {
        return $this->storage->count($this->name);
    }


    /**
     * @return void
     */
    public function resetAll(): void
    {
        $this->storage->unsetAll($this->name);
    }
}
