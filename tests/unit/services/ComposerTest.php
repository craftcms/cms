<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\test\TestCase;

/**
 * Unit tests for the config service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Oliver Stark <os@fortrabbit.com>
 * @since 4.0
 */
class ComposerTest extends TestCase
{
    public function testSortPackages(): void
    {
        $packages = [
            'craftcms/cms' => '4.5.5',
            'craftcms/contact-form' => '3.0.1',
            'vlucas/phpdotenv' => '^5.5.0',
            'php' => '~8.2.0',
            'verbb/smith' => '2.0.0',
            'clubstudioltd/craft-asset-rev' => '7.0.0',
            'craftcms/ckeditor' => '3.6.0',
            'verbb/navigation' => '2.0.21',
            'verbb/super-table' => '3.0.9',
            'rynpsc/craft-phone-number' => '2.1.0',
            'sebastianlenz/linkfield' => '2.1.5',
            'twig/string-extra' => '^3.5',
        ];

        $expected = [
            'php' => '~8.2.0',
            'clubstudioltd/craft-asset-rev' => '7.0.0',
            'craftcms/ckeditor' => '3.6.0',
            'craftcms/cms' => '4.5.5',
            'craftcms/contact-form' => '3.0.1',
            'rynpsc/craft-phone-number' => '2.1.0',
            'sebastianlenz/linkfield' => '2.1.5',
            'twig/string-extra' => '^3.5',
            'verbb/navigation' => '2.0.21',
            'verbb/smith' => '2.0.0',
            'verbb/super-table' => '3.0.9',
            'vlucas/phpdotenv' => '^5.5.0',
        ];

        Craft::$app->getComposer()->sortPackages($packages);
        self::assertSame($expected, $packages);
    }
}
