<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\Gql as GqlService;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Log;

abstract class Generator
{
    protected static function getContentFields(mixed $context): array
    {
        /** @var FieldLayoutProviderInterface|FieldLayout $context */
        try {
            $schema = app(GqlService::class)->getActiveSchema();
        } catch (GqlException $e) {
            Log::warning("Could not get the active GraphQL schema: {$e->getMessage()}", [__METHOD__]);
            report($e);

            return [];
        }

        $contentFieldGqlTypes = [];
        $layout = $context instanceof FieldLayout ? $context : $context->getFieldLayout();

        if (! $layout) {
            return $contentFieldGqlTypes;
        }

        foreach ($layout->getCustomFields() as $contentField) {
            /** @var Field $contentField */
            if ($contentField->includeInGqlSchema($schema)) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }
        }

        foreach ($layout->getGeneratedFields() as $generatedField) {
            $handle = $generatedField['handle'] ?? '';
            if ($handle !== '' && ! isset($contentFieldGqlTypes[$handle])) {
                $contentFieldGqlTypes[$handle] = Type::string();
            }
        }

        return $contentFieldGqlTypes;
    }
}
