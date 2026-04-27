<?php

declare(strict_types=1);

namespace craft\models;

use CraftCms\Cms\Gql\Data\GqlSchema as NewGqlSchema;

/**
 * @since 3.4.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\Data\GqlToken} instead.
 */
class GqlToken extends \CraftCms\Cms\Gql\Data\GqlToken
{
    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    public function getSchema(): ?GqlSchema
    {
        $schema = parent::getSchema();

        if (!$schema) {
            return null;
        }

        if ($schema instanceof GqlSchema) {
            return $schema;
        }

        return new GqlSchema($schema->toArray());
    }

    public function setSchema(NewGqlSchema $schema): void
    {
        parent::setSchema($schema instanceof GqlSchema ? $schema : new GqlSchema($schema->toArray()));
    }
}
