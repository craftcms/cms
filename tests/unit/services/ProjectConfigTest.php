<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Stub\Expected;
use Craft;
use craft\db\Table;
use craft\elements\Category;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\ProjectConfigData;
use craft\models\ReadOnlyProjectConfigData;
use craft\mutex\Mutex;
use craft\mutex\NullMutex;
use craft\services\ProjectConfig;
use craft\test\TestCase;
use Exception;
use UnitTester;
use yii\base\Exception as YiiException;
use yii\base\NotSupportedException;
use yii\caching\ArrayCache;
use yii\caching\CacheInterface;
use yii\caching\DummyCache;
use yii\mutex\FileMutex;
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

    protected static array $external = [
        'aa' => 'bb',
        'bb' => [
            'vc' => 'dd',
        ],
        'ee' => [11, 22, 33],
        'f' => 'g',
    ];

    private YiiMutex $_originalMutex;

    private ?CacheInterface $_originalCache = null;

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

        if ($this->_originalCache !== null) {
            Craft::$app->set('cache', $this->_originalCache);
            $this->_originalCache = null;
        }

        Craft::$app->getProjectConfig()->reset();
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
        $internal ??= $this->internal;
        $external ??= static::$external;

        $mockConfig = [
            'getExternalConfig' => fn() => new ReadOnlyProjectConfigData($external),
            'getInternalConfig' => new ReadOnlyProjectConfigData($internal),
            'persistInternalConfigValues' => null,
            'removeInternalConfigValuesByPaths' => null,
            'writeYamlFiles' => true,
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

    public function testSavingModifiedConfigDataOnlySavesNewChanges(): void
    {
        $persisted = [];
        $pc = $this->getProjectConfig(null, null, [
            'persistInternalConfigValues' => function(array $values) use (&$persisted) {
                $persisted[] = array_keys($values);
            },
            'storeYamlHistory' => null,
        ]);

        $pc->set('some.path', 'value');
        $pc->saveModifiedConfigData();
        $pc->saveModifiedConfigData();
        $pc->set('other.path', 'value');
        $pc->saveModifiedConfigData();

        $paths = array_values(array_intersect(array_merge(...$persisted), ['some.path', 'other.path']));
        self::assertSame(['some.path', 'other.path'], $paths);
    }

    public function testSavingModifiedConfigDataReleasesLockWithoutChanges(): void
    {
        $mutexPath = Craft::$app->getPath()->getTempPath() . '/mutex-' . StringHelper::randomString(8);
        Craft::$app->set('mutex', new FileMutex(['mutexPath' => $mutexPath]));
        $otherProcess = new FileMutex(['mutexPath' => $mutexPath, 'autoRelease' => false]);
        $pc = $this->getProjectConfig();

        $pc->acquireLock();
        self::assertFalse($otherProcess->acquire(ProjectConfig::MUTEX_NAME));

        $pc->saveModifiedConfigData();
        self::assertTrue($otherProcess->acquire(ProjectConfig::MUTEX_NAME));
        $otherProcess->release(ProjectConfig::MUTEX_NAME);
    }

    public function testWritingYamlFilesReplacesFilesWithoutClearingFolderFirst(): void
    {
        $globalProjectConfig = Craft::$app->getProjectConfig();
        $originalFolderName = $globalProjectConfig->folderName;
        $globalProjectConfig->folderName = 'project-' . StringHelper::randomString(8);
        $basePath = Craft::$app->getPath()->getProjectConfigPath();

        $entryTypeUid = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $fieldUid = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $config = [
            'dateModified' => 1,
            'entryTypes' => [$entryTypeUid => ['handle' => 'foo']],
            'fields' => [$fieldUid => ['handle' => 'body']],
        ];
        $pc = $this->make(ProjectConfig::class, [
            'getCurrentWorkingConfig' => function() use (&$config) {
                return new ProjectConfigData($config);
            },
        ]);
        $listFiles = fn() => array_map(
            fn($file) => substr($file, strlen($basePath) + 1),
            FileHelper::findFiles($basePath, ['except' => ['.gitignore']]),
        );

        try {
            file_put_contents("$basePath/.gitignore", '');
            FileHelper::writeToFile("$basePath/entryTypes/Foo--$entryTypeUid.yaml", "handle: Foo\n");
            FileHelper::writeToFile("$basePath/sections/old--cccccccc-cccc-4ccc-8ccc-cccccccccccc.yaml", "handle: old\n");
            FileHelper::writeToFile("$basePath/project.yaml", "dateModified: 0\n");
            FileHelper::writeToFile("$basePath/globalSets/old--eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee.yaml", "handle: old\n");
            FileHelper::createDirectory("$basePath/globalSets/.hidden");

            $pc->writeYamlFiles(true);

            $files = $listFiles();
            sort($files);
            self::assertSame([
                "entryTypes/foo--$entryTypeUid.yaml",
                "fields/body--$fieldUid.yaml",
                'project.yaml',
            ], $files);
            self::assertFileExists("$basePath/.gitignore");
            self::assertDirectoryDoesNotExist("$basePath/sections");
            self::assertDirectoryExists("$basePath/globalSets/.hidden");

            // A failed write leaves the existing files in place
            $config['fields']['dddddddd-dddd-4ddd-8ddd-dddddddddddd'] = ['handle' => 'summary'];
            chmod("$basePath/fields", 0555);
            try {
                $pc->writeYamlFiles(true);
                self::fail('writeYamlFiles() should have failed');
            } catch (YiiException $e) {
                self::assertStringContainsString('is not writable', $e->getMessage());
            }
            self::assertFileExists("$basePath/entryTypes/foo--$entryTypeUid.yaml");
            self::assertFileExists("$basePath/project.yaml");
            self::assertTrue($pc->getHadFileWriteIssues());
        } finally {
            if (is_dir("$basePath/fields")) {
                chmod("$basePath/fields", 0775);
            }
            FileHelper::removeDirectory($basePath);
            Craft::$app->getCache()->delete(ProjectConfig::FILE_ISSUES_CACHE_KEY);
            $globalProjectConfig->folderName = $originalFolderName;
        }
    }

    public function testApplyingConfigChangesDoesNotExposePartialElementSourcesToSectionHandlers(): void
    {
        $projectConfig = new ProjectConfig();
        $internalConfig = new ReadOnlyProjectConfigData([], $projectConfig);
        $currentWorkingConfig = new ProjectConfigData([], $projectConfig);

        $internalConfigProperty = new \ReflectionProperty(ProjectConfig::class, '_internalConfig');
        $internalConfigProperty->setValue($projectConfig, $internalConfig);

        $currentWorkingConfigProperty = new \ReflectionProperty(ProjectConfig::class, '_currentWorkingConfig');
        $currentWorkingConfigProperty->setValue($projectConfig, $currentWorkingConfig);

        $incomingConfig = [
            'elementSources' => [
                'craft\\elements\\Category' => [[
                    'defaultSort' => ['structure', 'asc'],
                    'tableAttributes' => ['link'],
                    'type' => 'native',
                    'key' => 'group:bedad454-861f-4d7f-b0f6-40ce023cffc5',
                    'disabled' => false,
                ]],
            ],
            'sections' => [
                'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa' => [
                    'siteSettings' => [
                        'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb' => [
                            'hasUrls' => true,
                            'uriFormat' => 'test/{slug}',
                            'template' => 'test/_entry',
                            'enabledByDefault' => true,
                        ],
                    ],
                    'entryTypes' => ['cccccccc-cccc-cccc-cccc-cccccccccccc'],
                ],
            ],
        ];

        $externalConfig = new ReadOnlyProjectConfigData($incomingConfig, $projectConfig);
        $externalConfigProperty = new \ReflectionProperty(ProjectConfig::class, '_externalConfig');
        $externalConfigProperty->setValue($projectConfig, $externalConfig);

        $originalProjectConfig = Craft::$app->getProjectConfig();
        $originalElementSources = Craft::$app->getElementSources();
        $handlerWasCalled = false;

        Craft::$app->set('projectConfig', $projectConfig);
        Craft::$app->set('elementSources', new \craft\services\ElementSources());

        $projectConfig->onAdd(ProjectConfig::PATH_SECTIONS . '.{uid}', function() use (&$handlerWasCalled) {
            $handlerWasCalled = true;
            Craft::$app->getElementSources()->getSources(Category::class);
        });

        try {
            $projectConfig->applyConfigChanges($incomingConfig);
        } finally {
            Craft::$app->set('projectConfig', $originalProjectConfig);
            Craft::$app->set('elementSources', $originalElementSources);
        }

        self::assertTrue($handlerWasCalled);
    }

    public function testParentHandlersFireWhenNestedValueCreatesParent(): void
    {
        $projectConfig = new ProjectConfig();
        $internalConfigProperty = new \ReflectionProperty(ProjectConfig::class, '_internalConfig');
        $internalConfigProperty->setValue($projectConfig, new ReadOnlyProjectConfigData($this->internal, $projectConfig));

        $handled = [];
        $projectConfig->onAdd('demo', function(ConfigEvent $event) use (&$handled) {
            $handled[] = [$event->oldValue, $event->newValue];
        });

        $projectConfig->set('demo.child.key', 1);

        self::assertSame([[null, ['child' => ['key' => 1]]]], $handled);
        self::assertSame(['child' => ['key' => 1]], $projectConfig->get('demo'));
    }

    public function testReadingBootKeysTakesOneCacheRead(): void
    {
        $cache = $this->_useCache(new class() extends ArrayCache {
            public int $reads = 0;

            public function get($key)
            {
                $this->reads++;
                return parent::get($key);
            }

            public function multiGet($keys)
            {
                $this->reads++;
                return parent::multiGet($keys);
            }
        });
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->get('system.name');
        $projectConfig->reset();
        $cache->reads = 0;

        self::assertNotNull($projectConfig->get('system.name'));
        self::assertNotNull($projectConfig->get(ProjectConfig::PATH_USERS));
        self::assertSame(1, $cache->reads);

        self::assertSame($projectConfig->get(ProjectConfig::PATH_SITES), $projectConfig->get()[ProjectConfig::PATH_SITES]);
        self::assertSame(3, $cache->reads);
    }

    /**
     * @dataProvider staleCacheProvider
     */
    public function testReloadingStaleCachedKeys(bool $configChanged): void
    {
        $this->_useCache(new ArrayCache());
        $projectConfig = Craft::$app->getProjectConfig();
        Db::insert(Table::PROJECTCONFIG, ['path' => 'laterRoot.value', 'value' => Json::encode('Before')]);
        self::assertSame('Before', $projectConfig->get('laterRoot.value'));

        Db::update(Table::PROJECTCONFIG, ['value' => Json::encode('After')], ['path' => 'laterRoot.value']);

        if ($configChanged) {
            $info = Craft::$app->getInfo();
            $info->configVersion = StringHelper::randomString(12);
            Craft::$app->saveInfo($info, ['configVersion']);
        } else {
            Craft::$app->getCache()->delete(ProjectConfig::STORED_CACHE_KEY . ':laterRoot');
        }

        $projectConfig->reset();
        self::assertSame('After', $projectConfig->get('laterRoot.value'));
        self::assertSame('After', $projectConfig->get()['laterRoot']['value']);
    }

    public function testReadingTheDatabaseOncePerRequestWhenTheCacheKeepsNothing(): void
    {
        $this->_useCache(new DummyCache());
        $projectConfig = Craft::$app->getProjectConfig();
        self::assertNotNull($projectConfig->get('system.name'));

        Db::insert(Table::PROJECTCONFIG, ['path' => 'laterRoot.value', 'value' => Json::encode('Later')]);

        self::assertNull($projectConfig->get('laterRoot.value'));
        self::assertArrayNotHasKey('laterRoot', $projectConfig->get());
    }

    public function testLeavingTheCacheWriteToTheRequestThatHoldsTheRebuildLock(): void
    {
        $this->_useCache(new ArrayCache());
        Craft::$app->set('mutex', new Mutex([
            'mutex' => new class() extends NullMutex {
                protected function acquireLock($name, $timeout = 0): bool
                {
                    return false;
                }
            },
        ]));
        Db::insert(Table::PROJECTCONFIG, ['path' => 'laterRoot.value', 'value' => Json::encode('Rebuilt')]);

        self::assertSame('Rebuilt', Craft::$app->getProjectConfig()->get('laterRoot.value'));
        self::assertFalse(Craft::$app->getCache()->get(ProjectConfig::STORED_CACHE_KEY));
    }

    public function testReadingTheRebuiltConfigAfterAnotherKeyIsEvicted(): void
    {
        $this->_useCache(new ArrayCache());
        $projectConfig = Craft::$app->getProjectConfig();
        Db::insert(Table::PROJECTCONFIG, ['path' => 'firstRoot.value', 'value' => Json::encode('Before')]);
        Db::insert(Table::PROJECTCONFIG, ['path' => 'secondRoot.value', 'value' => Json::encode('Second')]);
        $projectConfig->get('firstRoot.value');
        $projectConfig->reset();
        self::assertSame('Before', $projectConfig->get('firstRoot.value'));

        Db::update(Table::PROJECTCONFIG, ['value' => Json::encode('After')], ['path' => 'firstRoot.value']);
        Craft::$app->getCache()->delete(ProjectConfig::STORED_CACHE_KEY . ':secondRoot');

        self::assertSame('Second', $projectConfig->get('secondRoot.value'));
        self::assertSame('After', $projectConfig->get('firstRoot.value'));
        self::assertSame('After', $projectConfig->get()['firstRoot']['value']);
    }

    public function testSkippingTheCacheWriteWhenAnotherRequestRestoredTheEvictedKey(): void
    {
        $cache = $this->_useCache(new class() extends ArrayCache {
            public int $writes = 0;

            public function multiSet($items, $duration = null, $dependency = null)
            {
                $this->writes++;
                return parent::multiSet($items, $duration, $dependency);
            }
        });
        $projectConfig = Craft::$app->getProjectConfig();
        Db::insert(Table::PROJECTCONFIG, ['path' => 'laterRoot.value', 'value' => Json::encode('Later')]);
        $projectConfig->get('laterRoot.value');
        $projectConfig->reset();
        $key = ProjectConfig::STORED_CACHE_KEY . ':laterRoot';
        $entry = $cache->get($key);
        $cache->delete($key);
        Craft::$app->set('mutex', new Mutex([
            'mutex' => new class($cache, $key, $entry) extends NullMutex {
                public function __construct(private ArrayCache $cache, private string $key, private array $entry)
                {
                    parent::__construct();
                }

                protected function acquireLock($name, $timeout = 0): bool
                {
                    $this->cache->set($this->key, $this->entry);
                    return true;
                }
            },
        ]));
        $cache->writes = 0;

        self::assertSame('Later', $projectConfig->get('laterRoot.value'));
        self::assertSame(0, $cache->writes);
    }

    public static function staleCacheProvider(): array
    {
        return [
            'config changed' => [true],
            'key evicted' => [false],
        ];
    }

    /**
     * @template T of CacheInterface
     * @param T $cache
     * @return T
     */
    private function _useCache(CacheInterface $cache): CacheInterface
    {
        $this->_originalCache ??= Craft::$app->getCache();
        Craft::$app->set('cache', $cache);
        Craft::$app->getProjectConfig()->reset();

        return $cache;
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

    public static function getValueDataProvider(): array
    {
        return [
            ['a', false, 'b'],
            ['aa', false, null],
            ['aa', true, 'bb'],
            ['b', false, ['c' => 'd']],
            ['b.c', false, 'd'],
            ['ee.1', true, 22],
            ['ee', true, [11, 22, 33]],
            [null, true, static::$external],
        ];
    }

    public static function setValueDataProvider(): array
    {
        return [
            ['a', 'bar'],
            ['x', ['a' => 'b']],
            ['f', null],
        ];
    }
}
