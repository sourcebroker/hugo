<?php

namespace SourceBroker\Hugo\Domain\Model;

/**
 * Class ServiceResult
 *
 */
class ServiceResult
{

    /**
     * command
     *
     * @var string
     */
    protected $command = '';

    /**
     * commandOutput
     *
     * @var string
     */
    protected $commandOutput = '';

    /**
     * executedSuccessfully
     *
     * @var bool
     */
    protected $executedSuccessfully = false;

    /**
     * message
     *
     * @var string
     */
    protected $message = '';

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getCommandOutput(): string
    {
        return $this->commandOutput;
    }

    /**
     * @param string $commandOutput
     */
    public function setCommandOutput(string $commandOutput): void
    {
        $this->commandOutput = $commandOutput;
    }

    /**
     * @return bool
     */
    public function isExecutedSuccessfully(): bool
    {
        return $this->executedSuccessfully;
    }

    /**
     * @param bool $executedSuccessfully
     */
    public function setExecutedSuccessfully(bool $executedSuccessfully): void
    {
        $this->executedSuccessfully = $executedSuccessfully;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
