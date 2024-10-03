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
class Cms extends BaseResolver
{
    public static function match(string $className): bool
    {
        return str_starts_with($className, 'craft\\');
    }

    public static function getBaseUrl(): string
    {
        return Craft::$app->getDocs()->classReferenceUrl() . 'api/v5/';
    }

    public static function getPath(string $className, string $member = null, string $memberType = null): string
    {
        $path = strtolower(str_replace('\\', '-', $className)) . '.html';

        // Classes are always on their own pages, but each method and property is identified with an anchor:
        if ($member !== null) {
            $path = $path . match ($memberType) {
                'method' => '#method-' . strtolower($member),
                'property' => '#property-' . strtolower($member),
                'constant' => '#constants',
            };
        }

        return $path;
    }
}
