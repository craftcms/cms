<?php

namespace craft\db\mysql;

use Composer\Util\Platform;
use Craft;
use craft\db\DbShellCommand;
use craft\helpers\App;
use mikehaertl\shellcommand\Command as ShellCommand;

class BackupCommand extends DbShellCommand
{
    public ?array $ignoreTables = null;

    protected function getCommand(): ShellCommand
    {
        $serverVersion = App::normalizeVersion(Craft::$app->getDb()->getServerVersion());
        $isMySQL5 = version_compare($serverVersion, '8', '<');
        $isMySQL8 = version_compare($serverVersion, '8', '>=');

        // https://bugs.mysql.com/bug.php?id=109685
        $useSingleTransaction =
            ($isMySQL5 && version_compare($serverVersion, '5.7.41', '>=')) ||
            ($isMySQL8 && version_compare($serverVersion, '8.0.32', '>='));

        $command = parent::getCommand();
        $command->setCommand('mysqldump');
        $command->addArg('--defaults-file=', $this->createMysqlDumpConfigFile());
        $command->addArg('--add-drop-table');
        $command->addArg('--comments');
        $command->addArg('--create-options');
        $command->addArg('--dump-date');
        $command->addArg('--no-autocommit');
        $command->addArg('--routines');
        $command->addArg('--default-character-set=', Craft::$app->getConfig()->getDb()->charset);
        $command->addArg('--set-charset');
        $command->addArg('--triggers');
        $command->addArg('--no-tablespaces');

        if ($useSingleTransaction) {
            $command->addArg('--single-transaction');
        }

        // if there was output, then column-statistics is supported and we should disable it
        if ($this->supportsColumnStatistics()) {
            $command->addArg('--column-statistics=', '0');
        }

        return $this->callback
            ? ($this->callback)($command)
            : $command;
    }

    public function getExecCommand(): string
    {
        $schemaDump = (clone $this->getCommand())
            ->addArg('--no-data')
            ->addArg('--result-file=', '{file}')
            ->addArg('{database}')
            ->getExecCommand();

        $dataDump = (clone $this->getCommand())
            ->addArg('--no-create-info');

        foreach ($this->ignoreTables as $table) {
            $table = Craft::$app->getDb()->getSchema()->getRawTableName($table);
            $dataDump->addArg('--ignore-table=', "{database}.$table");
        }

        $dataDump = $dataDump
            ->addArg('{database}')
            ->getExecCommand();

        return "$schemaDump && $dataDump >> {file}";
    }

    protected function supportsColumnStatistics(): bool
    {
        // Find out if the db/dump client supports column-statistics
        $shellCommand = new ShellCommand();

        if (Platform::isWindows()) {
            $shellCommand->setCommand('mysqldump --help | findstr "column-statistics"');
        } else {
            $shellCommand->setCommand('mysqldump --help | grep "column-statistics"');
        }

        // If we don't have proc_open, maybe we've got exec
        if (!function_exists('proc_open') && function_exists('exec')) {
            $shellCommand->useExec = true;
        }

        $success = $shellCommand->execute();

        // if there was output, then column-statistics is supported
        return $success && $shellCommand->getOutput();
    }
}
