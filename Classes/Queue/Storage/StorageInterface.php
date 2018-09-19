<?php

namespace SourceBroker\Hugo\Queue\Storage;

interface StorageInterface
{
    /**
     * Removes and returns the first element of the list.
     *
     * @param string $key The key name of list.
     *
     * @return mixed
     */
    public function first($key);

    /**
     * Removes and returns the last element of the list.
     *
     * @param string $key The key name of list.
     *
     * @return mixed
     */
    public function last($key);

    /**
     * Append the value into the end of list.
     *
     * @param string $key   The key name of list.
     * @param mixed  $value Pushes value of the list.
     */
    public function append($key, $value);

    /**
     * Prepend the value into the beginning of list.
     *
     * @param string $key   The key name of list.
     * @param mixed  $value Pushes value of the list.
     */
    public function prepend($key, $value);

    /**
     * Count all elements in a list.
     *
     * @param string $key The key name of list.
     *
     * @return int
     */
    public function count($key);

    /**
     * Unset all element in a list
     *
     * @param string $key
     *
     * @return mixed
     */
    public function unsetAll($key);
}
