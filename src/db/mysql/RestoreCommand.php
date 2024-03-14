<?php

namespace craft\db\mysql;

use craft\db\DbShellCommand;
use mikehaertl\shellcommand\Command as ShellCommand;

class RestoreCommand extends DbShellCommand
{
    protected function getCommand(): ShellCommand
    {
        $command = new ShellCommand('mysql');
        $command->addArg('--defaults-file=', $this->createDumpConfigFile());
        $command->addArg('{database}');

        return $this->callback
            ? ($this->callback)($command)
            : $command;
    }

    public function getExecCommand(): string
    {
        return $this->getCommand()->getExecCommand() . ' < "{file}"';
    }
}
