<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\docs\resolvers;

/**
 * Yii class reference URL resolver.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
class Yii extends BaseResolver
{
    static function match(string $className): bool
    {
        return str_starts_with($className, 'yii\\');
    }

    static function getBaseUrl(): string
    {
        return 'https://www.yiiframework.com/doc/api/2.0/';
    }

    static function getPath(string $className, string $member = null, string $memberType = null): string
    {
        $path = strtolower(str_replace('\\', '-', $className)) . '.html';

        if ($member !== null) {
            $path = $path . match ($memberType) {
                'method' => '#' . strtolower($member) . '()-detail',
                'property' => '#$' . strtolower($member) . '-detail',
                'event' => '#events',
                'constant' => '#constants',
            };
        }

        return $path;
    }
}
