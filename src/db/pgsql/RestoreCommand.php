<?php

namespace craft\db\pgsql;

use Closure;
use craft\db\DbShellCommand;
use mikehaertl\shellcommand\Command as ShellCommand;
use yii\base\Exception;

class RestoreCommand extends DbShellCommand
{
    public bool $archiveFormat = false;
    public ?Closure $callback = null;

    protected function getCommand(): ShellCommand
    {
        $command = parent::getCommand();
        $command->setCommand($this->archiveFormat ? 'pg_restore' : 'psql');
        $command->addArg('--dbname=', '{database}');
        $command->addArg('--host=', '{server}');
        $command->addArg('--port=', '{port}');
        $command->addArg('--username=', '{user}');
        $command->addArg('--no-password');

        if ($this->archiveFormat) {
            $command->addArg('--file=', '{file}');
        }

        return $this->callback
            ? ($this->callback)($command)
            : $command;
    }

    /**
     * @throws Exception
     */
    public function getExecCommand(): string
    {
        return $this->pgsqlPasswordCommand()
            . $this->getCommand()->getExecCommand()
            . $this->archiveFormat ? '' : '< "{file}"';
    }
}
