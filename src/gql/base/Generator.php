<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use Craft;
use craft\base\Field;
use craft\behaviors\FieldLayoutBehavior;
use craft\errors\GqlException;
use craft\models\FieldLayout;
use GraphQL\Type\Definition\Type;

/**
 * Class Generator
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
abstract class Generator
{
    /**
     * Get content fields for a given context.
     *
     * @param mixed $context
     * @return array
     */
    protected static function getContentFields(mixed $context): array
    {
        /** @var FieldLayoutBehavior|FieldLayout $context */
        try {
            $schema = Craft::$app->getGql()->getActiveSchema();
        } catch (GqlException $e) {
            Craft::warning("Could not get the active GraphQL schema: {$e->getMessage()}", __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
            return [];
        }

        $contentFieldGqlTypes = [];
        $layout = $context instanceof FieldLayout ? $context : $context->getFieldLayout();

        if ($layout) {
            foreach ($layout->getCustomFields() as $contentField) {
                /** @var Field $contentField */
                if ($contentField->includeInGqlSchema($schema)) {
                    $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
                }
            }
    
            foreach ($layout->getGeneratedFields() as $generatedField) {
                $handle = $generatedField['handle'] ?? '';
                if ($handle !== '' && !isset($contentFieldGqlTypes[$handle])) {
                    $contentFieldGqlTypes[$handle] = Type::string();
                }
            }
        }

        return $contentFieldGqlTypes;
    }
}
