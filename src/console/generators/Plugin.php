<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\generators;

use Composer\Json\JsonManipulator;
use Craft;
use craft\base\Plugin as BasePlugin;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use Nette\PhpGenerator\PhpFile;
use yii\validators\EmailValidator;

/**
 * Creates a new Craft plugin.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class Plugin extends BaseGenerator
{
    private string $name;
    private string $developer;
    private bool $public;
    private string $targetDir;
    private string $relativeTargetDir;
    private string $handle;
    private string $packageName;
    private string $description;
    private ?string $license = null;
    private ?string $email = null;
    private ?string $repo = null;
    private string $minPhpVersion;
    private string $minCraftVersion;
    private string $rootNamespace;
    private bool $addEcs;
    private bool $addPhpStan;
    private array $craftConfig;

    public function run(): bool
    {
        $this->name = $this->controller->prompt('Plugin name:', [
            'required' => true,
        ]);

        $this->developer = $this->controller->prompt('Developer name:', [
            'required' => true,
        ]);

        $this->public = $this->controller->confirm('Do you plan on making it public?', true);

        $this->handle = $this->controller->prompt('Plugin handle:', [
            'default' => ($this->public ? '' : '_') . StringHelper::toKebabCase($this->name),
            'pattern' => '/^\_?[a-z]([a-z\\-]*[a-z])?$/',
        ]);

        if (!$this->public) {
            $this->handle = StringHelper::ensureLeft($this->handle, '_');
        }

        $this->targetDir = $this->directoryPrompt('Plugin location:', [
            'default' => sprintf('@root/plugins/%s', StringHelper::removeLeft($this->handle, '_')),
            'ensureEmpty' => true,
        ]);
        $this->relativeTargetDir = FileHelper::relativePath($this->targetDir);

        $defaultVendor = trim(preg_replace('/[^a-z\\-]/i', '', StringHelper::toKebabCase($this->developer)), '-');
        $this->packageName = $this->controller->prompt('Composer package name:', [
            'required' => true,
            'default' => $defaultVendor ? "$defaultVendor/$this->handle" : null,
            'pattern' => '/^[a-z][a-z\\-]*\\/[a-z][a-z\\-]*$/',
        ]);

        $this->description = $this->controller->prompt('Plugin description:');

        if ($this->public) {
            $this->license = $this->controller->select('How should the plugin be licensed?', [
                'mit' => 'MIT',
                'craft' => 'Craft (proprietary)',
            ]);

            $this->email = $this->controller->prompt('Support email:', [
                'validator' => function(string $input, ?string &$error): bool {
                    return (new EmailValidator())->validate($input, $error);
                },
            ]);

            $this->repo = $this->controller->prompt('GitHub repo URL:');
            if ($this->repo) {
                $this->repo = StringHelper::toLowerCase($this->repo);
                $this->repo = str_replace('http://', 'https://', $this->repo);
                $this->repo = StringHelper::removeLeft($this->repo, 'github.com/');
                $this->repo = StringHelper::ensureLeft($this->repo, 'https://github.com/');
            }
        }

        $this->craftConfig = Json::decodeFromFile('@craftcms/composer.json');

        $this->minPhpVersion = $this->controller->prompt('Minimum PHP version:', [
            'default' => ltrim($this->craftConfig['require']['php'], '^~'),
            'pattern' => '/^[\d\.]+$/',
        ]);

        $this->minCraftVersion = $this->controller->prompt('Minimum Craft CMS version:', [
            'default' => Craft::$app->getVersion(),
            'pattern' => '/^[\d\.]+\(-\w+(\.\d+)?)?$/',
        ]);

        $this->rootNamespace = $this->namespacePrompt('Root namespace:', [
            'default' => App::normalizeNamespace(str_replace('-', '', $this->packageName)),
        ]);

        $this->addEcs = $this->controller->confirm('Include ECS? (For automated code styling)', true);
        $this->addPhpStan = $this->controller->confirm('Include PHPStan? (For automated code quality checks)', true);
        $this->controller->stdout(PHP_EOL . 'Generating plugin files…' . PHP_EOL);

        if (!file_exists($this->targetDir)) {
            $this->controller->createDirectory($this->targetDir);
        }

        // Git config
        $this->writeGitAttributes();
        $this->writeGitIgnore();

        // Human info files
        if ($this->public) {
            $this->writeChangelog();
            $this->writeLicense();
        }
        $this->writeReadme();

        // More configs
        $this->writeComposerConfig();
        if ($this->addEcs) {
            $this->writeEcsConfig();
        }
        if ($this->addPhpStan) {
            $this->writePhpStanConfig();
        }

        // Plugin class
        $this->writePluginClass();

        $this->controller->stdout("Plugin files generated.\n\n");

        $installCommands = <<<MD
```
> composer require $this->packageName
> php craft plugin/install $this->handle
```
MD;

        $composerDir = dirname($this->composerFile);
        $composerConfig = Json::decodeFromFile($this->composerFile);
        $repositories = $composerConfig['repositories'] ?? [];

        foreach ($repositories as $repoConfig) {
            if (isset($repoConfig['type'], $repoConfig['url']) && $repoConfig['type'] === 'path') {
                $repoPath = FileHelper::absolutePath($repoConfig['url'], $composerDir, '/');
                // Get all the matching folders
                $flags = GLOB_MARK | GLOB_ONLYDIR;
                if (defined('GLOB_BRACE')) {
                    $flags |= GLOB_BRACE;
                }
                // Ensure environment-specific path separators are normalized to URL separators
                $folders = array_map(function($val) {
                    return FileHelper::normalizePath($val, '/');
                }, glob($repoPath, $flags));
                if (in_array($this->targetDir, $folders)) {
                    $message = <<<MD
**The plugin is ready to be installed!**
`$this->relativeTargetDir` is covered by the `{$repoConfig['url']}` repository in composer.json.
To install the plugin, run the following commands:

$installCommands
MD;
                    $this->controller->success($message);
                    $this->controller->stdout(PHP_EOL);
                    return true;
                }
            }
        }

        $addRepo = $this->controller->confirm($this->controller->markdownToAnsi("Create a new `path` repository in composer.json for `$this->relativeTargetDir`?"), true);
        $this->controller->stdout(PHP_EOL);

        if (!$addRepo) {
            $manualInstallInstructions = <<<MD
To add your plugin to Craft, add a `path` repository to composer.json (`https://getcomposer.org/doc/05-repositories.md#path`),
with the `url` pointing to `$this->relativeTargetDir`. Then run these commands:

$installCommands
MD;

            $this->controller->note($manualInstallInstructions);
            $this->controller->stdout(PHP_EOL);
            return true;
        }

        // Figure out the repo name
        if (ArrayHelper::isAssociative($repositories)) {
            // Find a unique repo name
            $i = 0;
            while (true) {
                $pluginRepoName = $this->handle . ($i ? "-$i" : '');
                if (!isset($repositories[$pluginRepoName])) {
                    break;
                }
                $i++;
            }
        } else {
            $pluginRepoName = count($repositories);
        }

        $pluginRepoConfig = [
            'type' => 'path',
            'url' => FileHelper::relativePath($this->targetDir, $composerDir, '/'),
        ];

        // First try adding it with JsonManipulator
        $manipulator = new JsonManipulator(file_get_contents($this->composerFile));
        if ($manipulator->addRepository($pluginRepoName, $pluginRepoConfig)) {
            $this->controller->writeToFile($this->composerFile, $manipulator->getContents());
        } else {
            $composerConfig['repositories'][$pluginRepoName] = $pluginRepoConfig;
            $this->controller->writeJson($this->composerFile, $composerConfig);
        }

        $message = <<<MD
**The plugin is ready to be installed!**
A new repository has been added to composer.json for `$this->relativeTargetDir`.
To install the plugin, run the following commands:

$installCommands
MD;
        $this->controller->stdout(PHP_EOL);
        $this->controller->success($message);
        $this->controller->stdout(PHP_EOL);
        return true;
    }

    private function writeGitAttributes(): void
    {
        $contents = <<<EOD
# Do not export those files in the Composer archive (lighter dependency)
.gitattributes export-ignore
.github/ export-ignore
.gitignore export-ignore
CHANGELOG.md export-ignore
README.md export-ignore
SECURITY.md export-ignore
composer.lock export-ignore
ecs.php export-ignore
package-lock.json export-ignore
package.json export-ignore
phpstan.neon export-ignore
stubs/ export-ignore
tests/ export-ignore

# Auto-detect text files and perform LF normalization
* text=auto

EOD;
        $this->controller->writeToFile("$this->targetDir/.gitattributes", $contents);
    }

    private function writeGitIgnore(): void
    {
        $contents = <<<EOD
*.DS_Store
*.idea/*
*.log
*Thumbs.db
.env
/node_modules
/vendor

EOD;
        $this->controller->writeToFile("$this->targetDir/.gitignore", $contents);
    }

    private function writeChangelog(): void
    {
        $contents = <<<MD
# Release Notes for $this->name

## 1.0.0
- Initial release

MD;
        $this->controller->writeToFile("$this->targetDir/CHANGELOG.md", $contents);
    }

    private function writeLicense(): void
    {
        if ($this->license === 'mit') {
            $contents = <<<MD
The MIT License (MIT)

Copyright (c) Pixel & Tonic, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

MD;
        } else {
            $contents = <<<MD
Copyright © $this->developer

Permission is hereby granted to any person obtaining a copy of this software
(the “Software”) to use, copy, modify, merge, publish and/or distribute copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

1. **Don’t plagiarize.** The above copyright notice and this license shall be
   included in all copies or substantial portions of the Software.

2. **Don’t use the same license on more than one project.** Each licensed copy
   of the Software shall be actively installed in no more than one production
   environment at a time.

3. **Don’t mess with the licensing features.** Software features related to
   licensing shall not be altered or circumvented in any way, including (but
   not limited to) license validation, payment prompts, feature restrictions,
   and update eligibility.

4. **Pay up.** Payment shall be made immediately upon receipt of any notice,
   prompt, reminder, or other message indicating that a payment is owed.

5. **Follow the law.** All use of the Software shall not violate any applicable
   law or regulation, nor infringe the rights of any other person or entity.

Failure to comply with the foregoing conditions will automatically and
immediately result in termination of the permission granted hereby. This
license does not include any right to receive updates to the Software or
technical support. Licensees bear all risk related to the quality and
performance of the Software and any modifications made or obtained to it,
including liability for actual and consequential harm, such as loss or
corruption of data, and any necessary service, repair, or correction.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES, OR OTHER
LIABILITY, INCLUDING SPECIAL, INCIDENTAL AND CONSEQUENTIAL DAMAGES, WHETHER IN
AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

MD;
        }

        $this->controller->writeToFile("$this->targetDir/LICENSE.md", $contents);
    }

    private function writeReadme(): void
    {
        $contents = <<<MD
# $this->name

$this->description

## Requirements

This plugin requires Craft CMS $this->minCraftVersion or later, and PHP $this->minPhpVersion or later.


MD;

        if ($this->public) {
            $contents .= <<<MD
## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “{$this->name}”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require $this->packageName

# tell Craft to install the plugin
./craft plugin/install $this->handle
```

MD;
        }

        $this->controller->writeToFile("$this->targetDir/README.md", $contents);
    }

    private function writeComposerConfig(): void
    {
        $config = array_filter([
            'name' => $this->packageName,
            'description' => $this->description,
            'type' => 'craft-plugin',
            'license' => $this->public ? ($this->license === 'mit' ? 'mit' : 'proprietary') : null,
            'support' => $this->public ? array_filter([
                'email' => $this->email,
                'issues' => $this->repo ? "$this->repo/issues?state=open" : null,
                'source' => $this->repo,
                'docs' => $this->repo,
                'rss' => $this->repo ? "$this->repo/releases.atom" : null,
            ]) : null,
            'require' => [
                'php' => ">=$this->minPhpVersion",
                'craftcms/cms' => "^$this->minCraftVersion",
            ],
            'require-dev' => [
                'craftcms/ecs' => $this->addEcs ? 'dev-main' : null,
                'craftcms/phpstan' => $this->addPhpStan ? 'dev-main' : null,
            ],
            'autoload' => [
                'psr-4' => [
                    "$this->rootNamespace\\" => 'src/',
                ],
            ],
            'extra' => [
                'handle' => $this->handle,
                'name' => $this->name,
                'developer' => $this->developer,
                'documentationUrl' => $this->public ? $this->repo : '',
            ],
            'scripts' => array_filter([
                'check-cs' => $this->addEcs ? 'ecs check --ansi' : null,
                'fix-cs' => $this->addEcs ? 'ecs check --ansi --fix' : null,
                'phpstan' => $this->addEcs ? 'phpstan --memory-limit=1G' : null,
            ]),
            'config' => [
                'sort-packages' => true,
                'platform' => $this->craftConfig['config']['platform'],
                'allow-plugins' => [
                    'yiisoft/yii2-composer' => true,
                    'craftcms/plugin-installer' => true,
                ],
            ],
        ]);

        $this->controller->writeJson("$this->targetDir/composer.json", $config);
    }

    private function writeEcsConfig(): void
    {
        $contents = <<<PHP
<?php

declare(strict_types=1);

use craft\ecs\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function(ECSConfig \$ecsConfig): void {
    \$ecsConfig->paths([
        __DIR__ . '/src',
        __FILE__,
    ]);

    \$ecsConfig->sets([
        SetList::CRAFT_CMS_4,
    ]);
};

PHP;
        $this->controller->writeToFile("$this->targetDir/ecs.php", $contents);
    }
    private function writePhpStanConfig(): void
    {
        $contents = <<<NEON
includes:
    - vendor/craftcms/phpstan/phpstan.neon

parameters:
    level: 4
    paths:
        - src

NEON;
        $this->controller->writeToFile("$this->targetDir/phpstan.neon", $contents);
    }

    private function writePluginClass(): void
    {
        $file = new PhpFile();

        $namespace = $file->addNamespace($this->rootNamespace)
            ->addUse(Craft::class)
            ->addUse(BasePlugin::class, 'BasePlugin');

        $class = $this->createClass('Plugin', BasePlugin::class, [
            self::CLASS_PROPERTIES => $this->properties(),
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $authorText = $this->developer . ($this->email ? " <$this->email>" : '');
        $class->setComment(<<<EOD
$this->name plugin

@method static Plugin getInstance()
@author $authorText
EOD);

        if ($this->public) {
            $licenseText = $this->license === 'mit' ? 'MIT' : 'https://craftcms.github.io/license/ Craft License';
            $class->addComment(<<<EOD
@copyright $this->developer
@license $licenseText
EOD);
        }

        $this->writePhpFile("$this->targetDir/src/Plugin.php", $file);
    }

    private function properties(): array
    {
        return [
            'schemaVersion',
        ];
    }

    private function methods(): array
    {
        return [
            'config' => <<<PHP
return [
    'components' => [
        // Define component configs here...
    ],
];
PHP,
            'init' => <<<PHP
parent::init();

// Defer most setup tasks until Craft is fully initialized
Craft::\$app->onInit(function() {
    // ...
});
PHP,
        ];
    }
}
