<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Commands\Concerns;

use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlHelper;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * @mixin Command
 */
trait ResolvesSchema
{
    protected function resolveSchema(Gql $gql): ?GqlSchema
    {
        if ($this->fullSchemaRequested()) {
            return GqlHelper::createFullAccessSchema();
        }

        /** @var string|null $schemaUid */
        $schemaUid = $this->option('schema');

        if ($schemaUid !== null) {
            $schema = $gql->getSchemaByUid($schemaUid);

            if ($schema === null) {
                $this->components->error("Invalid schema UUID: $schemaUid");

                return null;
            }

            return $schema;
        }

        /** @var string|null $accessToken */
        $accessToken = $this->option('token');

        if ($accessToken !== null) {
            try {
                $token = $gql->getTokenByAccessToken($accessToken);
            } catch (InvalidArgumentException) {
                $this->components->error("Invalid authorization token: $accessToken");

                return null;
            }

            $schema = $token->getSchema();

            if ($schema === null) {
                $this->components->error("No schema selected for token: $accessToken");

                return null;
            }

            return $schema;
        }

        try {
            return $gql->getActiveSchema();
        } catch (GqlException) {
            $schema = $gql->getPublicSchema();

            if ($schema === null) {
                $this->components->error("No public schema exists, and one can't be created because allowAdminChanges is disabled.");

                return null;
            }

            return $schema;
        }
    }

    protected function fullSchemaRequested(): bool
    {
        return (bool) $this->option('full-schema') || (bool) $this->option('fullSchema');
    }
}
