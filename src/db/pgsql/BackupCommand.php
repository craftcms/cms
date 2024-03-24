<?php

namespace craft\db\pgsql;

use Closure;
use Craft;
use craft\db\DbShellCommand;
use mikehaertl\shellcommand\Command as ShellCommand;
use yii\base\Exception;

class BackupCommand extends DbShellCommand
{
    public ?array $ignoreTables = null;
    public bool $archiveFormat = false;
    public ?Closure $callback = null;

    protected function getCommand(): ShellCommand
    {
        $command = parent::getCommand();
        $command->setCommand('pg_dump');

        $db = Craft::$app->getDb();
        $ignoreTables = $this->ignoreTables ?? $db->getIgnoredBackupTables();

        foreach ($ignoreTables as $table) {
            $table = $db->getSchema()->getRawTableName($table);
            $command->addArg('--exclude-table-data', "{schema}.$table");
        }

        if ($this->archiveFormat) {
            $command->addArg('--format=', 'custom');
        }

        $command
            ->addArg('--dbname=', '{database}')
            ->addArg('--host=', '{server}')
            ->addArg('--port=', '{port}')
            ->addArg('--username=', '{user}')
            ->addArg('--if-exists')
            ->addArg('--clean')
            ->addArg('--no-owner')
            ->addArg('--no-privileges')
            ->addArg('--no-acl')
            ->addArg('--file=', '{file}')
            ->addArg('--schema=', '{schema}');

        return $this->callback
            ? ($this->callback)($command)
            : $command;
    }

    /**
     * @throws Exception
     */
    public function getExecCommand(): string
    {
        return $this->pgsqlPasswordCommand() . $this->getCommand()->getExecCommand();
    }
}
