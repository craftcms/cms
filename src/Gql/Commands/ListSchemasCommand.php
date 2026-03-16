<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Gql\Gql;
use Illuminate\Console\Command;
use Override;

class ListSchemasCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:graphql:list-schemas';

    #[Override]
    protected $description = 'Lists all GraphQL schemas.';

    #[Override]
    protected $aliases = ['graphql/list-schemas'];

    public function handle(Gql $gql): int
    {
        $schemas = $gql->getSchemas();

        if ($schemas === []) {
            $this->line('No GraphQL schemas exist.');

            return self::SUCCESS;
        }

        $this->table(
            ['UID', 'Name'],
            collect($schemas)
                ->map(fn ($schema) => [$schema->uid, $schema->name])
                ->all(),
        );

        return self::SUCCESS;
    }
}
