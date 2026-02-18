<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Setup;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PDOException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

final class DatabaseCredentialsCommand extends Command
{
    use CraftCommand;

    protected $signature = 'craft:setup:db-creds
        {--driver= : The database driver to use. Either `\'mysql\'` for MySQL, `\'mariadb\'` for MariaDB or `\'pgsql\'` for PostgreSQL.}
        {--host= : The database server name or IP address. Usually `\'localhost\'` or `\'127.0.0.1\'`.}
        {--port= : The database server port. Defaults to 3306 for MySQL and MariaDB and 5432 for PostgreSQL.}
        {--username= : The database username to connect with.}
        {--password= : The database password to connect with.}
        {--database= : The name of the database to select.}
        {--schema= : The schema that Postgres is configured to use by default (PostgreSQL only).}
        {--prefix= : The table prefix to add to all database tables. This can be no more than 5 characters, and must be all lowercase.}
    ';

    protected $description = 'Stores new DB connection settings to the `.env` file.';

    protected $aliases = ['setup/db-creds', 'setup:db', 'setup/db'];

    private string $driver;

    private string $host;

    private int $port;

    private string $user;

    private ?string $password = null;

    private string $database;

    private string $schema;

    private string $prefix;

    public function handle(): int
    {
        $badUserCredentials = false;

        top:

        $envDriver = Config::get('database.default') ?? Env::get('DB_CONNECTION', Env::get('CRAFT_DB_DRIVER'));
        $this->driver = $this->option('driver') ?? select(
            label: 'Which database driver are you using?',
            options: [
                'mysql' => 'MySQL',
                'mariadb' => 'MariaDB',
                'pgsql' => 'PostgreSQL',
            ],
            default: $this->driver ?? $envDriver,
            validate: [Rule::in('mysql', 'pgsql')]
        );

        $this->host = $this->option('host') ?? strtolower(text(
            label: 'Database server name or IP address:',
            default: $this->host ?? Config::get("database.connections.{$this->driver}.host") ?? Env::get('DB_HOST', Env::get('CRAFT_DB_SERVER', '127.0.0.1')),
            required: true,
        ));

        $this->port = (int) ($this->option('port') ?? text(
            label: 'Database port:',
            default: $this->port ?? Config::get("database.connections.{$this->driver}.port") ?? Env::get('DB_PORT', Env::get('CRAFT_DB_PORT', in_array($this->driver, ['mysql', 'mariadb']) ? '3306' : '5432')),
            required: true,
        ));

        userCredentials:

        $this->user = $this->option('username') ?? text(
            label: 'Database username:',
            default: $this->user ?? Config::get("database.connections.{$this->driver}.username") ?? Env::get('DB_USERNAME', Env::get('CRAFT_DB_USER', 'root')),
        );

        if (! $this->password = $this->option('password')) {
            $envPassword = Config::get("database.connections.{$this->driver}.password") ?? Env::get('DB_PASSWORD', Env::get('CRAFT_DB_PASSWORD'));
            if ($envPassword && confirm('Use the password provided by $DB_PASSWORD?')) {
                $this->password = $envPassword;
            } else {
                $this->password = password(
                    label: 'Database password:',
                );
            }
        }

        /** @phpstan-ignore-next-line */
        if ($badUserCredentials) {
            $badUserCredentials = false;
            goto test;
        }

        if (! $this->input->isInteractive() && ! $this->option('database')) {
            $this->error('The --database option must be set.');

            return self::FAILURE;
        }

        $this->database = $this->option('database') ?? text(
            label: 'Database name:',
            default: $this->database ?? Config::get("database.connections.{$this->driver}.database") ?? Env::get('DB_DATABASE', Env::get('CRAFT_DB_NAME')),
            required: true,
        );

        if ($this->driver === 'pgsql') {
            $this->schema = $this->option('schema') ?? text(
                label: 'Database schema:',
                default: $this->schema ?? Config::get("database.connections.{$this->driver}.schema") ?? Env::get('DB_SCHEMA', Env::get('CRAFT_DB_SCHEMA', 'public')),
                required: true,
            );
        }

        $this->prefix = $this->option('prefix') ?? text(
            label: 'Database table prefix:',
            default: $this->prefix ?? Config::get("database.connections.{$this->driver}.prefix") ?? Env::get('DB_PREFIX', Env::get('CRAFT_DB_PREFIX', '')),
            validate: ['max:5'],
            transform: function ($value) {
                if (! $value) {
                    return $value;
                }

                return Str::finish($value, '_');
            }
        );

        $this->components->info('Testing database credentials...');

        test:

        $config = array_filter([
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->user,
            'password' => $this->password,
            'database' => $this->database,
            'prefix' => $this->prefix,
            'schema' => $this->schema ?? null,
        ]);

        try {
            /** @var \Illuminate\Database\Connection $connection */
            $connection = DB::build($config);
            $connection->getPdo();
        } catch (PDOException $e) {
            // Error codes:
            // 7:    Name or service not known (server)
            // 7:    could not connect to server (port)
            // 7:    password authentication failed (username)
            // 7:    no password supplied (password)
            // 1045: Access denied for user (username, password)
            // 1049: Unknown database (database)
            // 2002: Connection timed out (server)
            $this->components->error('Failed: '.$e->getMessage());

            // Test some common issues
            $message = $e->getMessage();

            if ($this->host === 'localhost' && $message === 'SQLSTATE[HY000] [2002] No such file or directory') {
                // means the Unix socket doesn't exist - https://stackoverflow.com/a/22927341/1688568
                // try 127.0.0.1 instead...
                $this->warn('Trying with 127.0.0.1 instead of localhost ... ');
                $this->host = '127.0.0.1';
                goto test;
            }

            if ($this->port === 3306 && $message === 'SQLSTATE[HY000] [2002] Connection refused') {
                // try 8889 instead (default MAMP port)...
                $this->warn('Trying with port 8889 instead of 3306 ... ');
                $this->port = 8889;
                goto test;
            }

            if (
                str_contains($message, 'Access denied for user') ||
                str_contains($message, 'no password supplied') ||
                str_contains($message, 'password authentication failed for user') ||
                str_contains($message, "role \"$this->user\" does not exist")
            ) {
                $this->warn('Try with a different username and/or password.');
                $badUserCredentials = true;
                goto userCredentials;
            }

            if (! $this->input->isInteractive()) {
                return self::FAILURE;
            }

            goto top;
        }

        $this->components->success('Success! Saving database credentials to your .env file ...');

        $path = app()->environmentFilePath();

        if (! is_null(Env::get('DB_URL'))) {
            Env::writeVariable('DB_URL', "$this->driver://$this->user:$this->password@$this->host:$this->port/$this->database", app()->environmentFilePath());
        } else {
            Env::writeVariable('DB_CONNECTION', $this->driver, $path, true);
            Env::writeVariable('DB_HOST', $this->host, $path, true);
            Env::writeVariable('DB_PORT', $this->port, $path, true);
            Env::writeVariable('DB_USERNAME', $this->user, $path, true);
            Env::writeVariable('DB_PASSWORD', $this->password, $path, true);
            Env::writeVariable('DB_DATABASE', $this->database, $path, true);

            if ($this->prefix) {
                Env::writeVariable('DB_PREFIX', $this->prefix, $path, true);
            }

            if (isset($this->schema)) {
                Env::writeVariable('DB_SCHEMA', $this->schema, $path, true);
            }
        }

        $this->components->success('Database credentials saved successfully.');

        Config::set("database.connections.{$this->driver}", $config);
        Config::set('database.connections.db2', $config);

        return self::SUCCESS;
    }
}
