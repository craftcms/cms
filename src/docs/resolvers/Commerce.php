<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\docs\resolvers;

use Craft;

/**
 * Craft CMS class reference URL resolver.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
class Commerce extends Cms
{
    static function match(string $className): bool
    {
        return str_starts_with($className, 'craft\\commerce\\');
    }

    static function getBaseUrl(): string
    {
        return Craft::$app->getDocs()->classReferenceUrl() . 'commerce/api/v5/';
    }
}
