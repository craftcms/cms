<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\base\Field;
use craft\base\GqlSchemaAwareFieldInterface;

/**
 * Class BaseGenerator
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
abstract class BaseGenerator
{
    /**
     * Get content fields for a given context.
     *
     * @param mixed $context
     * @return array
     */
    protected static function getContentFields($context): array
    {
        $contentFields = $context->getFields();
        $contentFieldGqlTypes = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            if (!$contentField instanceof GqlSchemaAwareFieldInterface || $contentField->getExistsForCurrentGqlSchema()) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }
        }

        return $contentFieldGqlTypes;
    }
}
