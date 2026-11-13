<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\services\Composer;
use craft\services\Path;
use craft\test\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;
use yii\base\InvalidConfigException;

/**
 * Unit tests for the config service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Oliver Stark <os@fortrabbit.com>
 * @since 4.0
 */
class ComposerTest extends TestCase
{
    private string $directory;
    private string|false $originalPath;
    private string $originalRoot;
    private string $originalStorage;
    private Path $originalPathService;

    protected function _before(): void
    {
        parent::_before();
        $this->directory = sys_get_temp_dir() . '/craft composer ' . uniqid();
        FileHelper::createDirectory($this->directory);
        $this->originalPath = getenv('PATH');
        $this->originalRoot = Craft::getAlias('@root');
        $this->originalStorage = Craft::getAlias('@storage');
        $this->originalPathService = Craft::$app->getPath();
        putenv("PATH=$this->directory");
        Craft::setAlias('@root', $this->directory);
        Craft::setAlias('@storage', "$this->directory/storage");
        Craft::$app->set('path', new Path());
    }

    protected function _after(): void
    {
        putenv($this->originalPath === false ? 'PATH' : "PATH=$this->originalPath");
        Craft::setAlias('@root', $this->originalRoot);
        Craft::setAlias('@storage', $this->originalStorage);
        Craft::$app->set('path', $this->originalPathService);
        FileHelper::removeDirectory($this->directory);
        parent::_after();
    }

    /** @dataProvider availabilityDataProvider */
    public function testAvailability(?string $output, int $exitCode, bool $available): void
    {
        if ($output !== null) {
            $this->writeExecutable('echo ' . var_export($output, true) . "; exit($exitCode);");
        }

        $composer = new Composer();
        self::assertSame($available, $composer->getIsAvailable());
        self::assertSame($available, $composer->getUnavailableReason() === null);
    }

    public static function availabilityDataProvider(): array
    {
        return [
            'missing' => [null, 0, false],
            'Composer 1' => ['Composer version 1.10.27 2023-09-29', 0, false],
            'below minimum' => ['Composer version 2.1.99 2021-01-01', 0, false],
            'minimum prerelease' => ['Composer version 2.2.0-RC1 2021-01-01', 0, false],
            'minimum' => ['Composer version 2.2.0 2021-12-22', 0, true],
            'newer' => ['Composer version 2.11.1 2026-10-30', 0, true],
            'legacy version format' => ['Composer 2.2.24 2024-06-10', 0, true],
            'unrecognized' => ['Not Composer', 0, false],
            'malformed version' => ['Composer version 2.2.0garbage', 0, false],
            'unsuccessful' => ['Composer version 2.11.1 2026-10-30', 1, false],
        ];
    }

    /** @dataProvider uiAvailabilityDataProvider */
    public function testUiCacheDoesNotAffectFreshChecks(bool $available): void
    {
        $this->writeExecutable('echo ' . var_export($available ? 'Composer version 2.2.0' : 'Not Composer', true) . ';');
        self::assertSame($available, (new Composer())->getIsAvailableForUi());

        // Simulate Composer breaking or being repaired between requests, without changing PATH.
        $this->writeExecutable('echo ' . var_export($available ? 'Not Composer' : 'Composer version 2.2.0', true) . ';');
        $composer = new Composer();
        self::assertSame($available, $composer->getIsAvailableForUi());
        self::assertSame($available, $composer->getUnavailableReason(true) === null);
        self::assertSame(!$available, $composer->getUnavailableReason() === null);
    }

    public static function uiAvailabilityDataProvider(): array
    {
        return [
            'cached available, now broken' => [true],
            'cached unavailable, now repaired' => [false],
        ];
    }

    /** @dataProvider unavailableOperationDataProvider */
    public function testUnavailableOperationsLeaveComposerFilesUnchanged(string $operation, ?array $packages): void
    {
        $json = '{"require":{"example/package":"1.0.0"}}';
        file_put_contents("$this->directory/composer.json", $json);
        file_put_contents("$this->directory/composer.lock", '{}');

        try {
            (new Composer())->$operation($packages);
            self::fail('Composer should be required before changing packages.');
        } catch (InvalidConfigException $e) {
            self::assertStringContainsString('Composer 2.2.0 or later', $e->getMessage());
        }

        self::assertSame($json, file_get_contents("$this->directory/composer.json"));
        self::assertSame('{}', file_get_contents("$this->directory/composer.lock"));
    }

    public static function unavailableOperationDataProvider(): array
    {
        return [
            'install' => ['install', null],
            'update' => ['install', ['example/package' => '2.0.0']],
            'remove' => ['uninstall', ['example/package']],
        ];
    }

    public function testSystemComposerExecutionAndRollback(): void
    {
        $this->writeExecutable(<<<'PHP'
if (in_array('--version', $argv, true)) {
    echo 'Composer version 2.2.0';
    exit(0);
}
echo json_encode(['arguments' => array_slice($argv, 1), 'home' => getenv('COMPOSER_HOME')]);
exit(is_file(__DIR__ . '/fail') ? 1 : 0);
PHP);
        $jsonPath = "$this->directory/composer.json";
        file_put_contents($jsonPath, '{"require":{}}');
        file_put_contents("$this->directory/composer.lock", '{}');

        $composer = new Composer();
        $output = '';
        $composer->install(['example/package' => '2.0.0'], function($type, $buffer) use (&$output) {
            $output .= $buffer;
        });

        $result = Json::decode($output);
        self::assertSame('update', $result['arguments'][0]);
        self::assertContains('example/package', $result['arguments']);
        self::assertContains('--no-scripts', $result['arguments']);
        self::assertContains($this->directory, $result['arguments']);
        self::assertDirectoryExists($result['home']);
        self::assertSame('2.0.0', Json::decodeFromFile($jsonPath)['require']['example/package']);

        $beforeFailure = file_get_contents($jsonPath);
        touch("$this->directory/fail");
        try {
            $composer->install(['example/package' => '3.0.0']);
            self::fail('A failing system Composer command should throw.');
        } catch (ProcessFailedException) {
            self::assertSame($beforeFailure, file_get_contents($jsonPath));
        }
    }

    private function writeExecutable(string $code): void
    {
        file_put_contents("$this->directory/composer.php", "<?php\n$code\n");
        if (DIRECTORY_SEPARATOR === '\\') {
            file_put_contents("$this->directory/composer.bat", '@echo off' . "\r\n" . '"' . PHP_BINARY . '" "%~dp0composer.php" %*');
        } else {
            file_put_contents("$this->directory/composer", "#!/bin/sh\nexec " . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$this->directory/composer.php") . ' "$@"');
            chmod("$this->directory/composer", 0755);
        }
    }

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
