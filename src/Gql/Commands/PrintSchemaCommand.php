<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Gql\Commands\Concerns\ResolvesSchema;
use CraftCms\Cms\Gql\Gql;
use GraphQL\Utils\SchemaPrinter;
use Illuminate\Console\Command;
use Override;

class PrintSchemaCommand extends Command
{
    use CraftCommand;
    use ResolvesSchema;

    #[Override]
    protected $signature = 'craft:graphql:print-schema
        {--schema= : The GraphQL schema UUID.}
        {--token= : The authorization token used to resolve the GraphQL schema.}
        {--full-schema : Whether the full schema should be printed.}
        {--fullSchema : Whether the full schema should be printed. [DEPRECATED]}
    ';

    #[Override]
    protected $description = 'Prints a GraphQL schema.';

    #[Override]
    protected $aliases = ['graphql/print-schema'];

    public function handle(Gql $gql): int
    {
        $schema = $this->resolveSchema($gql);

        if ($schema === null) {
            return self::FAILURE;
        }

        $this->output->write(SchemaPrinter::doPrint($gql->getSchemaDef($schema, true)));

        return self::SUCCESS;
    }
}
