<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use craft\web\Application;
use craft\web\assets\updater\UpdaterAsset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Exceptions\MigrateException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Container\Attributes\Give;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Process\Process;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @internal
 */
abstract class BaseUpdaterController
{
    use AuthorizesRequests;

    public const string ACTION_PRECHECK = 'precheck';

    public const string ACTION_RECHECK_COMPOSER = 'recheck-composer';

    public const string ACTION_COMPOSER_INSTALL = 'composer-install';

    public const string ACTION_COMPOSER_REMOVE = 'composer-remove';

    public const string ACTION_FINISH = 'finish';

    /** @var array The data associated with the current update */
    protected array $data = [];

    public function __construct(
        protected Request $request,
        protected GeneralConfig $generalConfig,
        protected Composer $composer,
        protected Plugins $plugins,
        protected Updates $updates,
    ) {
        if ($this->request->route()->getActionMethod() === 'index') {
            return;
        }

        if (! is_null($this->request->input('data'))) {
            try {
                $data = Crypt::decrypt($this->request->input('data', ''));
            } catch (DecryptException) {
                throw ValidationException::withMessages([
                    'data' => t('Invalid data.'),
                ]);
            }

            $this->data = Json::decode($data);
        }
    }

    public function index(#[Give('Craft')] Application $craft): View
    {
        // Load the updater JS
        $view = $craft->getView();
        $view->registerAssetBundle(UpdaterAsset::class);

        $this->data = $this->initialData();
        $state = $this->realInitialState();
        $state['data'] = $this->hashedData();

        $segments = $this->request->actionSegments();
        $idJs = Json::encode(implode('/', $segments));
        $stateJs = Json::encode($state);
        HtmlStack::js("Craft.updater = (new Craft.Updater($idJs)).setState($stateJs);");

        return view('_special/updater', [
            'title' => $this->pageTitle(),
        ]);
    }

    public function precheck(): Response
    {
        $postState = $this->data['postPrecheckState'];

        if (PHP::testIniSet()) {
            return $this->send($postState);
        }

        $errors = [];

        $timeLimit = (int) trim(ini_get('max_execution_time'));
        if ($timeLimit !== 0 && $timeLimit < 120) {
            $errors[] = t('{name} should be at least {value}.', [
                'name' => '`max_execution_time`',
                'value' => '`120`',
            ]);
        }

        $memoryLimit = PHP::configValueInBytes('memory_limit');
        if ($memoryLimit !== -1 && $memoryLimit < 1024 * 1024 * 256) {
            $errors[] = t('{name} should be at least {value}.', [
                'name' => '`memory_limit`',
                'value' => '`256M`',
            ]);
        }

        if (! empty($errors)) {
            $error = t('Please fix the following in your {file} file before proceeding:',
                ['file' => '`php.ini`']).
                "\n\n".implode("\n\n", $errors);

            return $this->send([
                'error' => $error,
                'options' => [
                    ['label' => t('Learn how'), 'url' => 'https://craftcms.com/knowledge-base/php-ini'],
                    $this->actionOption(t('Check again'), self::ACTION_PRECHECK),
                    $this->actionOption(t('Continue anyway'), $postState['nextAction'], $postState),
                ],
            ]);
        }

        return $this->send($postState);
    }

    public function recheckComposer(): Response
    {
        return $this->send($this->realInitialState());
    }

    public function composerInstall(): Response
    {
        $output = '';

        try {
            $this->composer->install($this->data['requirements'], function ($type, $buffer) use (&$output) {
                if ($type === Process::OUT) {
                    $output .= $buffer;
                }
            });

            Log::info("Updated Composer requirements.\nOutput: $output", [__METHOD__]);
        } catch (Throwable $e) {
            Log::error('Error updating Composer requirements: '.$e->getMessage()."\nOutput: $output", [__METHOD__]);
            report($e);

            if (str_contains($output, 'Your requirements could not be resolved to an installable set of packages.')) {
                $error = t('Composer was unable to install the updates due to a dependency conflict.');
            } else {
                $error = t('Composer was unable to install the updates.');
            }

            return $this->sendComposerError($error, $e, $output);
        }

        return $this->send($this->postComposerInstallState());
    }

    public function composerRemove(): Response
    {
        $packages = [$this->data['packageName']];
        $output = '';

        try {
            $this->composer->uninstall($packages, function ($type, $buffer) use (&$output) {
                if ($type === Process::OUT) {
                    $output .= $buffer;
                }
            });
            Log::info("Updated Composer requirements.\nOutput: $output", [__METHOD__]);
            $this->data['removed'] = true;
        } catch (Throwable $e) {
            Log::error('Error updating Composer requirements: '.$e->getMessage()."\nOutput: $output", [__METHOD__]);
            report($e);

            return $this->sendComposerError(t('Composer was unable to remove the plugin.'), $e, $output);
        }

        return $this->send($this->postComposerInstallState());
    }

    public function finish(): Response
    {
        // Disable maintenance mode
        app()->maintenanceMode()->deactivate();

        return $this->send([
            'finished' => true,
            'returnUrl' => $this->returnUrl(),
        ]);
    }

    /**
     * Returns the page title
     */
    abstract protected function pageTitle(): string;

    /**
     * Returns the initial data.
     */
    abstract protected function initialData(): array;

    /**
     * Returns the initial state for the updater JS.
     *
     * @param  bool  $force  Whether to go through with the update even if Maintenance Mode is enabled
     */
    abstract protected function initialState(bool $force = false): array;

    /**
     * Returns the real initial state for the updater JS.
     *
     * @param  bool  $force  Whether to go through with the update even if Maintenance Mode is enabled
     */
    final protected function realInitialState(bool $force = false): array
    {
        $state = $this->initialState($force);

        // If there's nothing to do here, then no precheck is needed
        if (! isset($state['nextAction'])) {
            return $state;
        }

        // Store the "initial" state in a postPrecheckState, and make the real initial state the precheck
        $this->data['postPrecheckState'] = $state;

        return [
            'nextAction' => self::ACTION_PRECHECK,
            'status' => $this->actionStatus(self::ACTION_PRECHECK),
        ];
    }

    /**
     * Returns the state data for after [[actionComposerInstall()]] is done.
     */
    abstract protected function postComposerInstallState(): array;

    /**
     * Returns the return URL provided by the `return` body param, if it’s a valid URL.
     */
    protected function findReturnUrl(): ?string
    {
        $returnUrl = request('return');

        if ($returnUrl === null) {
            return null;
        }

        if (str_contains($returnUrl, '{')) {
            throw new BadRequestHttpException("Invalid return URL: $returnUrl");
        }

        return $returnUrl;
    }

    /**
     * Returns the return URL that should be passed with a finished state.
     */
    abstract protected function returnUrl(): string;

    /**
     * Ensures that composer.json can be found.
     *
     * @return bool Whether composer.json can be found
     */
    protected function ensureComposerJson(): bool
    {
        try {
            $this->composer->getJsonPath();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Returns the initial state if composer.json couldn't be found.
     *
     * @see ensureComposerJson()
     */
    protected function noComposerJsonState(): array
    {
        return [
            'error' => t('Your composer.json file could not be located. Try setting the CRAFT_COMPOSER_PATH constant in index.php to its location on the server.'),
            'errorDetails' => 'define(\'CRAFT_COMPOSER_PATH\', \'path/to/composer.json\');',
            'options' => [
                $this->actionOption(t('Try again'), self::ACTION_RECHECK_COMPOSER, ['submit' => true]),
            ],
        ];
    }

    /**
     * Sends a state response.
     */
    protected function send(array $state = []): Response
    {
        // Encode and hash the data
        $state['data'] = $this->hashedData();

        return new JsonResponse($state);
    }

    /**
     * Sends a "next action" state response.
     *
     * @param  string  $nextAction  The next action that should be run
     */
    protected function sendNextAction(string $nextAction, array $state = []): Response
    {
        $state = $this->actionState($nextAction, $state);

        return $this->send($state);
    }

    /**
     * Sends a "finished" state response.
     */
    protected function sendFinished(array $state = []): Response
    {
        $state = $this->finishedState($state);

        return $this->send($state);
    }

    /**
     * Sends an "error" state response for a Composer error
     *
     * @param  string  $error  The status message to show
     * @param  Throwable  $e  The exception that was thrown
     * @param  string  $output  The Composer output
     */
    protected function sendComposerError(string $error, Throwable $e, string $output, array $state = []): Response
    {
        $state['error'] = $error;
        $state['errorDetails'] = $this->composerErrorDetails($e, $output);

        $state['options'] = [
            [
                'label' => t('Troubleshoot'),
                'url' => 'https://craftcms.com/knowledge-base/failed-updates',
            ],
            [
                'label' => t('Send for help'),
                'email' => 'support@craftcms.com',
                'subject' => 'Composer error',
            ],
        ];

        return $this->send($state);
    }

    /**
     * Returns an option definition that kicks off a new action.
     */
    protected function actionOption(string $label, string $action, array $state = []): array
    {
        $state['label'] = $label;

        return $this->actionState($action, $state);
    }

    /**
     * Sets the state info for the given next action.
     */
    protected function actionState(string $nextAction, array $state = []): array
    {
        $state['nextAction'] = $nextAction;

        if (! isset($state['status'])) {
            $state['status'] = $this->actionStatus($nextAction);
        }

        return $state;
    }

    /**
     * Returns the status message for the given action.
     *
     * @throws RuntimeException if $action isn't valid
     */
    protected function actionStatus(string $action): string
    {
        return match ($action) {
            self::ACTION_PRECHECK => t('Checking environment…'),
            self::ACTION_RECHECK_COMPOSER => t('Checking…'),
            self::ACTION_COMPOSER_INSTALL => t('Updating Composer dependencies (this may take a minute)…',
                [
                    'command' => '`composer install`',
                ]),
            self::ACTION_COMPOSER_REMOVE => t('Updating Composer dependencies (this may take a minute)…',
                [
                    'command' => '`composer remove`',
                ]),
            self::ACTION_FINISH => t('Finishing up…'),
            default => throw new RuntimeException('Invalid action: '.$action),
        };
    }

    /**
     * Sets the state info for when the job is done.
     */
    protected function finishedState(array $state = []): array
    {
        if (! isset($state['status']) && ! isset($state['error'])) {
            $state['status'] = t('All done!');
        }

        $state['finished'] = true;
        $state['returnUrl'] = $this->returnUrl();

        return $state;
    }

    /**
     * Runs the migrations for a given list of handles.
     *
     * @param  string[]  $handles
     */
    protected function runMigrations(array $handles): ?Response
    {
        try {
            $this->updates->runMigrations($handles);
        } catch (MigrateException $e) {
            $ownerName = $e->ownerName;
            $ownerHandle = $e->ownerHandle;
            /** @var Throwable $e */
            $e = $e->getPrevious();

            $error = 'Migration failed: '.$e->getMessage();

            Log::error($error, [__METHOD__]);
            report($e);

            $options = [
                [
                    'label' => t('Troubleshoot'),
                    'url' => 'https://craftcms.com/knowledge-base/failed-updates',
                ],
            ];

            if ($ownerHandle !== 'craft' && ($plugin = $this->plugins->getPlugin($ownerHandle)) !== null) {
                $email = $plugin->developerEmail;
            }

            $email ??= 'support@craftcms.com';

            $options[] = [
                'label' => t('Send for help'),
                'email' => $email,
                'subject' => $ownerName.' update failure',
            ];

            return $this->send([
                'error' => t('One of {name}’s migrations failed.', ['name' => $ownerName]),
                'errorDetails' => $e::class.': '.$e->getMessage(),
                'options' => $options,
            ]);
        }

        return null;
    }

    /**
     * Attempts to install a plugin by its handle.
     *
     *
     * @return array Array with installation results
     */
    protected function installPluginInternal(string $handle, ?string $edition = null): array
    {
        try {
            $this->plugins->installPlugin($handle, $edition);
            $success = true;
            $errorDetails = null;
        } catch (Throwable $e) {
            $success = false;

            Log::error('Plugin installation failed: '.$e->getMessage(), [__METHOD__]);

            $errorDetails = $e::class.': '.$e->getMessage();
        }

        // Put the real response back
        return [$success, $errorDetails];
    }

    /**
     * Returns the hashed data for JS.
     */
    private function hashedData(): string
    {
        return Crypt::encrypt(Json::encode($this->data));
    }

    /**
     * Returns the error details for a Composer error.
     *
     * @param  Throwable  $e  The exception that was thrown
     * @param  string  $output  The Composer output
     */
    private function composerErrorDetails(Throwable $e, string $output): string
    {
        $details = [];

        $message = trim($e->getMessage());
        if ($message && $message !== 'An error occurred') {
            $details[] = t('Error:').' '.$message;
        }

        $output = trim(strip_tags($output));
        if ($output) {
            $details[] = t('Composer output:').' '.$output;
        }

        if (empty($details)) {
            $details[] = 'Exception of class '.$e::class.' was thrown.';
        }

        return implode("\n\n", $details);
    }
}
