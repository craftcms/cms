<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use CraftCms\Cms\Support\Env;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Override;

final class ServeCommand extends Command
{
    #[Override]
    protected $signature = 'workbench:serve
        {--port= : Override the stable port}
        {--url= : Public URL, optionally containing a port placeholder}
        {--fresh : Rebuild and reseed the database}';

    #[Override]
    protected $description = 'Build and serve the Workbench application.';

    public function handle(): int
    {
        $port = $this->port();

        if ($port === null) {
            return self::FAILURE;
        }

        $url = $this->url($port);

        if ($url === null) {
            return self::FAILURE;
        }

        if ($this->call('package:sync-skeleton') !== self::SUCCESS) {
            return self::FAILURE;
        }

        Env::writeVariable('APP_URL', $url, app()->environmentFilePath(), overwrite: true);
        Config::set('app.url', $url);

        $databaseExists = is_file(database_path('database.sqlite'));

        if ($this->call('package:create-sqlite-db') !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (($this->option('fresh') || ! $databaseExists) &&
            $this->call('workbench:build') !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->components->info("Workbench available at $url");

        return $this->call('serve', [
            '--host' => '127.0.0.1',
            '--port' => $port,
            '--tries' => 1,
        ]);
    }

    private function port(): ?int
    {
        if (($port = $this->option('port')) === null) {
            $hash = hexdec(substr(hash('sha256', dirname(__DIR__, 4)), 0, 4));

            return 8000 + ($hash % 1000);
        }

        if (! ctype_digit((string) $port) || (int) $port < 1 || (int) $port > 65535) {
            $this->components->error('The port must be an integer between 1 and 65535.');

            return null;
        }

        return (int) $port;
    }

    private function url(int $port): ?string
    {
        $url = str_replace(
            '{port}',
            (string) $port,
            (string) ($this->option('url') ?? "http://127.0.0.1:$port"),
        );

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            $this->components->error('The URL must be a valid HTTP or HTTPS URL.');

            return null;
        }

        return rtrim($url, '/');
    }
}
