<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Stub\Expected;
use Craft;
use craft\helpers\StringHelper;
use craft\models\ReadOnlyProjectConfigData;
use craft\mutex\Mutex;
use craft\mutex\NullMutex;
use craft\services\ProjectConfig;
use craft\test\TestCase;
use Exception;
use UnitTester;
use yii\base\NotSupportedException;
use yii\mutex\Mutex as YiiMutex;

/**
 * Unit tests for ProjectConfig service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ProjectConfigTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    protected array $internal = [
        'a' => 'b',
        'b' => [
            'c' => 'd',
        ],
        'e' => [1, 2, 3],
        'f' => 'g',
        'randomString' => 'Entirely random',
        'dateModified' => 1609452000,
    ];

    protected array $external = [
        'aa' => 'bb',
        'bb' => [
            'vc' => 'dd',
        ],
        'ee' => [11, 22, 33],
        'f' => 'g',
    ];

    private YiiMutex $_originalMutex;

    protected function _before(): void
    {
        parent::_before();
        $this->_originalMutex = Craft::$app->getMutex();
        Craft::$app->set('mutex', new Mutex([
            'mutex' => new NullMutex(),
        ]));
    }

    protected function _after(): void
    {
        parent::_after();
        Craft::$app->set('mutex', $this->_originalMutex);
    }

    /**
     * @param array|null $internal
     * @param array|null $external
     * @param array $additionalConfig
     * @return ProjectConfig
     * @throws Exception
     */
    protected function getProjectConfig(?array $internal = null, ?array $external = null, array $additionalConfig = []): ProjectConfig
    {
        $internal = $internal ?? $this->internal;
        $external = $external ?? $this->external;

        $mockConfig = [
            'getExternalConfig' => function() use ($external) {
                return new ReadOnlyProjectConfigData($external);
            },
            'getInternalConfig' => new ReadOnlyProjectConfigData($internal),
            'persistInternalConfigValues' => null,
            'removeInternalConfigValuesByPaths' => null,
            'updateYamlFiles' => true,
            'updateConfigVersion' => true,
        ];
        $mockConfig = array_merge($mockConfig, $additionalConfig);

        return $this->make(ProjectConfig::class, $mockConfig);
    }

    /**
     * Test if rebuilding project config ignores the `readOnly` flag.
     */
    public function testRebuildIgnoresReadOnly(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $readOnly = $projectConfig->readOnly;
        $projectConfig->readOnly = true;

        $failToSet = function() use ($projectConfig) {
            $projectConfig->set('oops', true);
        };

        // Must trigger exception
        $this->tester->expectThrowable(NotSupportedException::class, $failToSet);

        // Must not trigger exception
        $projectConfig->rebuild();

        $projectConfig->readOnly = $readOnly;
    }

    /**
     * @param string|null $path
     * @param bool $useExternal
     * @param mixed $expectedValue
     * @throws Exception
     * @dataProvider getValueDataProvider
     */
    public function testGettingValue(?string $path, bool $useExternal, mixed $expectedValue): void
    {
        $actualValue = $this->getProjectConfig()->get($path, $useExternal);
        self::assertSame($expectedValue, $actualValue);
    }

    /**
     * @param string $path
     * @param mixed $value
     * @dataProvider setValueDataProvider
     */
    public function testSettingValue(string $path, mixed $value): void
    {
        $projectConfig = $this->getProjectConfig();
        $projectConfig->set($path, $value);

        $actual = $projectConfig->get($path);
        self::assertSame($value, $actual);
    }

    public function testSettingNewValueModifiesTimestamp(): void
    {
        $projectConfig = $this->getProjectConfig();
        $path = 'randomString';
        $initialValue = $projectConfig->get($path);
        $initialTimestamp = $projectConfig->get('dateModified');

        $projectConfig->set($path, $initialValue);
        self::assertSame($initialTimestamp, $projectConfig->get('dateModified'));

        $projectConfig->set($path, StringHelper::randomString());
        self::assertNotSame($initialTimestamp, $projectConfig->get('dateModified'));
    }

    public function testSettingValueIgnoresExternalValue(): void
    {
        $internal = [
            'common' => [
                'foo' => 'bar',
                'bar' => 'baz',
            ],
        ];

        $external = [
            'common' => [
                'box' => 'bax',
            ],
        ];
        $pc = $this->getProjectConfig($internal, $external);

        $pc->set('common.fizz', 'buzz');

        // Expect project config to have the merged value
        self::assertSame('buzz', $pc->get('common.fizz'));
        self::assertSame('bar', $pc->get('common.foo'));

        // Expect the external storage to be unaware of anything
        self::assertSame('bax', $pc->get('common.box', true));
        self::assertSame(null, $pc->get('common.fizz', true));
    }

    public function testPreventChangesIfReadOnly(): void
    {
        $pc = $this->getProjectConfig();
        $pc->readOnly = true;
        $this->expectExceptionMessage('while in read-only');
        $pc->set('path', 'value');
    }

    public function testSettingValueChangesTimestamp(): void
    {
        $pc = $this->getProjectConfig();
        $timestamp = $pc->get('dateModified');
        $pc->set('path', 'value');
        self::assertNotSame($timestamp, $pc->get('dateModified'));
    }

    public function testEventsFiredAndDeltaStored(): void
    {
        $pc = $this->getProjectConfig(null, null, [
            'trigger' => Expected::atLeastOnce(),
            'storeYamlHistory' => Expected::atLeastOnce(),
        ]);
        Craft::$app->set('projectConfig', $pc);

        $pc->set('some.path', 'value');
        $pc->saveModifiedConfigData();

        $pc->remove('some.path');
        $pc->saveModifiedConfigData();
    }

    public function getConfigProvider(): array
    {
        return [
            [
                ['a' => 'b'],
                ['b' => 'c'],
                ['c' => 'a'],
                true,
                [
                    'a' => null,
                    'b' => 'c',
                    'c' => null,
                ],
            ],
            [
                ['a' => 'b'],
                null,
                ['c' => 'a'],
                true,
                [
                    'a' => 'b',
                    'b' => null,
                    'c' => null,
                ],
            ],
            [
                ['a' => 'b'],
                ['b' => 'c'],
                ['c' => 'a'],
                false,
                [
                    'a' => null,
                    'b' => null,
                    'c' => 'a',
                ],
            ],
        ];
    }

    public function setConfigProvider(): array
    {
        return [
            [
                'a.b.c',
                ['foo' => 'bar'],
            ],
            [
                'a.b',
                ['foo' => 'bar', 'bar' => ['baz']],
            ],
        ];
    }

    public function getValueDataProvider(): array
    {
        return [
            ['a', false, 'b'],
            ['aa', false, null],
            ['aa', true, 'bb'],
            ['b', false, ['c' => 'd']],
            ['b.c', false, 'd'],
            ['ee.1', true, 22],
            ['ee', true, [11, 22, 33]],
            [null, true, $this->external],
        ];
    }

    public function setValueDataProvider(): array
    {
        return [
            ['a', 'bar'],
            ['x', ['a' => 'b']],
            ['f', null],
        ];
    }
}
