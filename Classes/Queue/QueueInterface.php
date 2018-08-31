<?php


namespace SourceBroker\Hugo\Queue;


interface QueueInterface
{
    /**
     * @param $value
     *
     * @return mixed
     */
    public function push($value);

    /**
     * @return mixed
     */
    public function pop();

    /**
     * @return int
     */
    public function count(): int;

    /**
     * @return void
     */
    public function resetAll(): void;
}
