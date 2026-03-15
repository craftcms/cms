<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Gql\Commands\CreateTokenCommand;
use CraftCms\Cms\Gql\Commands\DumpSchemaCommand;
use CraftCms\Cms\Gql\Commands\ListSchemasCommand;
use CraftCms\Cms\Gql\Commands\PrintSchemaCommand;
use Illuminate\Support\ServiceProvider;

class GqlServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            ListSchemasCommand::class,
            PrintSchemaCommand::class,
            DumpSchemaCommand::class,
            CreateTokenCommand::class,
        ]);
    }
}
