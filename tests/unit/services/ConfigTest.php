<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\test\TestCase;

/**
 * Unit tests for the config service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Oliver Stark <os@fortrabbit.com>
 * @since 4.0
 */
class ConfigTest extends TestCase
{
    public function testDotEnvPathIsNotABooleanString(): void
    {
        Craft::setAlias('@root', CRAFT_TESTS_PATH);

        $config = Craft::$app->getConfig();
        $path = $config->getDotEnvPath();
        $this->assertEquals(CRAFT_TESTS_PATH . '/.env', $path);
    }
}
