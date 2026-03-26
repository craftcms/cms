<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Gql\Commands\Concerns\ResolvesSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Support\Str;
use GraphQL\Utils\SchemaPrinter;
use Illuminate\Console\Command;
use Override;

class DumpSchemaCommand extends Command
{
    use CraftCommand;
    use ResolvesSchema;

    public const string GQL_SCHEMA_EXTENSION = '.graphql';

    #[Override]
    protected $signature = 'craft:graphql:dump-schema
        {--schema= : The GraphQL schema UUID.}
        {--token= : The authorization token used to resolve the GraphQL schema.}
        {--full-schema : Whether the full schema should be dumped.}
        {--fullSchema : Whether the full schema should be dumped. [DEPRECATED]}
    ';

    #[Override]
    protected $description = 'Dumps a GraphQL schema to a file.';

    #[Override]
    protected $aliases = ['graphql/dump-schema'];

    public function handle(Gql $gql): int
    {
        $schema = $this->resolveSchema($gql);

        if ($schema === null) {
            return self::FAILURE;
        }

        $filename = Str::slug((string) $schema->name, '_').self::GQL_SCHEMA_EXTENSION;
        $schemaDump = SchemaPrinter::doPrint($gql->getSchemaDef($schema, true));

        $this->components->task(
            "Dumping GraphQL schema to $filename",
            fn () => file_put_contents($filename, $schemaDump),
        );

        return self::SUCCESS;
    }
}
