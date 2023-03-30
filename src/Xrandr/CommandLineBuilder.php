<?php

namespace Xrandr;

use InvalidArgumentException;

class CommandLineBuilder
{
    private $commands = [];

    public function addCommand($command)
    {
        if (!is_string($command)) {
            throw new InvalidArgumentException('Argument command must be of type string');
        }

        $this->commands[] = $command;

        return $this;
    }

    public function clearCommands()
    {
        $this->setCommands([]);
    }

    public function getCommandLine()
    {
        return implode(' ', $this->getCommands());
    }

    public function getCommands()
    {
        return $this->commands;
    }

    public function setCommands($commands)
    {
        if (!is_array($commands)) {
            throw new InvalidArgumentException('Argument commands must be of type array');
        }

        $this->commands = $commands;

        return $this;
    }
}
