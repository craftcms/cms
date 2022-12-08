<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\composer\InvalidPluginException;
use craft\console\Controller;
use craft\console\generators\BaseGenerator;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Composer;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use yii\console\ExitCode;

/**
 * Generates the scaffolding for a new system component.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class MakeController extends Controller
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering generator types.
     *
     * Generator types must extend [[BaseGenerator]].
     * ---
     * ```php
     * use craft\console\controllers\MakeController;
     * use craft\events\RegisterComponentTypesEvent;
     * use yii\base\Event;
     *
     * Event::on(MakeController::class,
     *     Fields::EVENT_REGISTER_GENERATOR_TYPES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyGenerator::class;
     *     }
     * );
     * ```
     */
    public const EVENT_REGISTER_GENERATOR_TYPES = 'registerGeneratorTypes';

    /**
     * @inheritdoc
     */
    public $defaultAction = 'generate';

    /**
     * @var bool Whether to add DocBlock comments to the generated class constants, properties, and methods.
     */
    public bool $withDocblocks = false;

    /**
     * @var bool
     */
    private bool $_app = false;

    /**
     * @var string|null $module The module ID to generate the component for.
     */
    private ?string $_module = null;

    /**
     * @var string|null $plugin The plugin handle to generate the component for.
     */
    private ?string $_plugin = null;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        // Don't include app/module/plugin since `module` conflicts with yii\base\Controller::$module
        return array_merge(parent::options($actionID), [
            'withDocblocks',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getActionOptionsHelp($action): array
    {
        return array_merge(parent::getActionOptionsHelp($action), [
            'app' => [
                'type' => 'bool',
                'default' => false,
                'comment' => 'Generate the component for Craft CMS itself.',
            ],
            'module' => [
                'type' => 'string|null',
                'default' => null,
                'comment' => 'The module ID to generate the component for.',
            ],
            'plugin' => [
                'type' => 'string|null',
                'default' => null,
                'comment' => 'The plugin handle to generate the component for.',
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function runAction($id, $params = []): int
    {
        $this->_app = (bool)(ArrayHelper::remove($params, 'app') ?? false);
        $this->_module = ArrayHelper::remove($params, 'module');
        $this->_plugin = ArrayHelper::remove($params, 'plugin');

        return parent::runAction($id, $params);
    }

    /**
     * Generates the scaffolding for a new system component.
     *
     * All commands other than `make module` and `make plugin` require one of the following options to be passed:
     *
     * - `--app`
     * - `--module=<module-id>`
     * - `--plugin=<plugin-handle>`
     *
     * If `--with-docblocks` is passed, generated classes will include DocBlock comments copied from their base class.
     *
     * @param string|null $type The type of component to generate.
     */
    public function actionGenerate(?string $type = null): int
    {
        if ($type === null) {
            return $this->run('/help', ['make']);
        }

        if (!$this->interactive) {
            $this->stderr("This command must be run interactively.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $usedParams = array_filter([
            $this->_app ? '--app' : null,
            $this->_module ? '--module' : null,
            $this->_plugin ? '--plugin' : null,
        ]);
        $usedParamCount = count($usedParams);

        if (in_array($type, ['module', 'plugin'])) {
            if ($usedParamCount !== 0) {
                $this->stdout(sprintf("`make $type` doesn’t support the %s %s.\n", implode(' ', $usedParams), $usedParamCount === 1 ? 'option' : 'options'), Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $module = null;
            $basePath = FileHelper::normalizePath(Craft::getAlias('@root'), '/');
            $composerFile = FileHelper::normalizePath(Craft::$app->getComposer()->getJsonPath(), '/');
            $baseNamespace = null;
        } else {
            if ($usedParamCount === 0) {
                $this->stdout("`make $type` must specify an --app, --module, or --plugin option.\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            } elseif ($usedParamCount !== 1) {
                $this->stdout("`make $type` must only specify --app, --module, or --plugin, but not multiple.\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            if ($this->_app) {
                $module = Craft::$app;
            } elseif ($this->_module) {
                $module = Craft::$app->getModule($this->_module);
                if (!$module) {
                    $this->stdout("No module exists with the ID \"$this->_module\". ", Console::FG_RED);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
            } else {
                $pluginsService = Craft::$app->getPlugins();
                try {
                    $module = $pluginsService->getPlugin($this->_plugin) ?? $pluginsService->createPlugin($this->_plugin);
                } catch (InvalidPluginException $e) {
                    $this->stdout($e->getMessage(), Console::FG_RED);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }

            $basePath = FileHelper::normalizePath($module->getBasePath(), '/');
            $composerFile = FileHelper::normalizePath(FileHelper::findClosestFile($basePath, [
                'only' => ['composer.json'],
            ]), '/');

            if (!$composerFile) {
                $this->stdout("No `composer.json` file found at or above `$basePath`.\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            // Make sure we have an autoload root that encompasses the module's base path
            $composerDir = dirname($composerFile);
            $baseNamespace = null;
            foreach (Composer::autoloadConfigFromFile($composerFile) as $rootNamespace => $rootPath) {
                $rootDir = FileHelper::absolutePath($rootPath, $composerDir);
                if ($rootDir === $basePath) {
                    $baseNamespace = rtrim($rootNamespace, '\\');
                    break;
                } elseif (FileHelper::isWithin($basePath, $rootDir)) {
                    $relativeBasePath = FileHelper::relativePath($basePath, $rootDir);
                    $baseNamespace = sprintf('%s\\%s', $rootNamespace, App::normalizeNamespace($relativeBasePath));
                    break;
                }
            }
            if ($baseNamespace === null) {
                $this->stdout("$basePath is not autoloadable from any of the autoload roots in $composerFile.\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        /** @var string|BaseGenerator|null $class */
        $class = ArrayHelper::firstWhere($this->types(), function(string $class) use ($type) {
            /** @var string|BaseGenerator $class */
            return $class::name() === $type;
        });

        if (!$class) {
            $this->stdout("Invalid generator type: $type\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        /** @var BaseGenerator $generator */
        $generator = Craft::createObject([
            'class' => $class,
            'controller' => $this,
            'module' => $module,
            'basePath' => $basePath,
            'baseNamespace' => $baseNamespace,
            'composerFile' => $composerFile,
        ]);

        return $generator->run() ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * @inheritdoc
     */
    public function getActionArgsHelp($action): array
    {
        $args = parent::getActionArgsHelp($action);

        if ($action->id === 'generate') {
            $args['type']['comment'] .= " Options include:\n";

            /** @var array<string|BaseGenerator> $types */
            $types = ArrayHelper::index($this->types(), function(string $type) {
                /** @var string|BaseGenerator $type */
                return $type::name();
            });

            ksort($types);

            foreach ($types as $type) {
                /** @var string|BaseGenerator $type */
                $args['type']['comment'] .= $this->markdownToAnsi(sprintf("- `%s`: %s", $type::name(), $type::description())) . PHP_EOL;
            }
        }

        return $args;
    }

    /**
     * @phpstan-return class-string<BaseGenerator>[]
     */
    private function types(): array
    {
        $generatorsDir = dirname(__DIR__) . '/generators';
        $types = array_map(
            fn(string $file) => sprintf('craft\\console\\generators\\%s', pathinfo($file, PATHINFO_FILENAME)),
            FileHelper::findFiles($generatorsDir, [
                'only' => ['*.php'],
                'except' => ['BaseGenerator.php'],
            ])
        );

        $event = new RegisterComponentTypesEvent([
            'types' => $types,
        ]);
        $this->trigger(self::EVENT_REGISTER_GENERATOR_TYPES, $event);

        return $event->types;
    }
}
