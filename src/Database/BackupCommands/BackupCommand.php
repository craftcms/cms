<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\BackupCommands;

use CraftCms\Cms\Support\Str;
use Illuminate\Database\Connection;
use Symfony\Component\Process\ExecutableFinder;

abstract class BackupCommand
{
    public function __construct(
        protected Connection $connection,
        protected string $filePath,
        /** @var string[] */
        protected array $ignoreTables = [],
    ) {}

    abstract public function backup(): string;

    abstract public function restore(): string;

    /** @return array{database: string, host: string, port: string, username: string, password: string, schema: string} */
    protected function getConnectionConfig(): array
    {
        return [
            'database' => (string) ($this->connection->getConfig('database') ?? ''),
            'host' => (string) ($this->connection->getConfig('host') ?? ''),
            'port' => (string) ($this->connection->getConfig('port') ?? ''),
            'username' => (string) ($this->connection->getConfig('username') ?? ''),
            'password' => (string) ($this->connection->getConfig('password') ?? ''),
            'schema' => (string) ($this->connection->getConfig('schema') ?? 'public'),
        ];
    }

    protected function resolveExecutable(string $name): string
    {
        $path = new ExecutableFinder()->find($name) ?: $name;

        return escapeshellarg($path);
    }

    protected function tableName(string $table): string
    {
        return Str::start(trim($table), $this->connection->getTablePrefix());
    }
}
