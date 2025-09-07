<?php

namespace CraftCms\Cms\Http\Controllers\Updates;

use Composer\Semver\Comparator;
use Craft;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Controllers\BaseUpdaterController;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RequirementsChecker;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Process\Process;
use Throwable;

use function CraftCms\Cms\normalizeVersion;

/**
 * @internal
 *
 * @since 6.0.0
 */
final class UpdaterController extends BaseUpdaterController
{
    public const string ACTION_FORCE_UPDATE = 'force-update';

    public const string ACTION_BACKUP = 'backup';

    public const string ACTION_SERVER_CHECK = 'server-check';

    public const string ACTION_REVERT = 'revert';

    public const string ACTION_MIGRATE = 'migrate';

    public function __construct(
        Request $request,
        GeneralConfig $generalConfig,
        Composer $composer,
        Plugins $plugins,
        Updates $updates,
    ) {
        parent::__construct($request, $generalConfig, $composer, $plugins, $updates);

        if ($request->has('install') && $this->request->fullUrlIs(action([self::class, 'index']))) {
            abort_unless($request->user()->can('performUpdates'), 403, 'You do not have permission to perform updates.');
        }
    }

    public function forceUpdate(): Response
    {
        return $this->send($this->realInitialState(force: true));
    }

    public function backup(): Response
    {
        try {
            app('Craft')->getDb()->backup();
        } catch (Throwable $e) {
            Log::error('Error backing up the database: '.$e->getMessage(), [__METHOD__]);

            if (! empty($this->data['install'])) {
                $firstAction = $this->actionOption(Craft::t('app', 'Revert the update'), self::ACTION_REVERT);
            } else {
                $firstAction = $this->finishedState([
                    'label' => Craft::t('app', 'Abort the update'),
                    'status' => Craft::t('app', 'Update aborted.'),
                ]);
            }

            return $this->send([
                'error' => Craft::t('app', 'Couldn’t backup the database. How would you like to proceed?'),
                'options' => [
                    $firstAction,
                    $this->actionOption(Craft::t('app', 'Try again'), self::ACTION_BACKUP),
                    $this->actionOption(Craft::t('app', 'Continue anyway'), self::ACTION_MIGRATE),
                ],
            ]);
        }

        return $this->sendNextAction(self::ACTION_MIGRATE);
    }

    public function revert(): Response
    {
        $output = '';

        try {
            $this->composer->install($this->data['current'], function ($type, $buffer) use (&$output) {
                if ($type === Process::OUT) {
                    $output .= $buffer;
                }
            });
            Log::info("Reverted Composer requirements.\nOutput: $output", [__METHOD__]);
            $this->data['reverted'] = true;
        } catch (Throwable $e) {
            Log::error('Error reverting Composer requirements: '.$e->getMessage()."\nOutput: $output", [__METHOD__]);

            return $this->sendComposerError(Craft::t('app', 'Composer was unable to revert the updates.'), $e, $output);
        }

        return $this->send($this->postComposerInstallState());
    }

    public function serverCheck(): Response
    {
        $reqCheck = new RequirementsChecker;
        $reqCheck->checkCraft();

        $errors = [];

        if ($reqCheck->result['summary']['errors'] > 0) {
            foreach ($reqCheck->getResult()['requirements'] as $req) {
                if ($req['failed'] === true) {
                    $errors[] = $req['memo'];
                }
            }
        }

        if (! empty($errors)) {
            Log::warning("The server doesn't meet Craft's new requirements:\n - ".implode("\n - ", $errors), [__METHOD__]);

            return $this->send([
                'error' => Craft::t('app', 'The server doesn’t meet Craft’s new requirements:').' '.implode(', ', $errors),
                'options' => [
                    $this->actionOption(Craft::t('app', 'Revert update'), self::ACTION_REVERT),
                    $this->actionOption(Craft::t('app', 'Check again'), self::ACTION_SERVER_CHECK),
                ],
            ]);
        }

        // Are there any migrations to run?
        $installedHandles = array_keys($this->data['install']);
        $pendingHandles = $this->updates->pendingMigrationHandles();

        if (! empty(array_intersect($pendingHandles, $installedHandles))) {
            $backup = $this->generalConfig->getBackupOnUpdate();

            return $this->sendNextAction($backup ? self::ACTION_BACKUP : self::ACTION_MIGRATE);
        }

        // Nope - we're done!
        return $this->sendFinished();
    }

    public function migrate(): Response
    {
        if (! empty($this->data['install'])) {
            $handles = array_keys($this->data['install']);
        } else {
            $handles = array_merge($this->data['migrate']);
        }

        return $this->runMigrations($handles) ?? $this->sendFinished();
    }

    /**
     * {@inheritdoc}
     */
    protected function pageTitle(): string
    {
        return Craft::t('app', 'Updater');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialData(): array
    {
        $data = [];

        // Set the return URL, if any
        if (($returnUrl = $this->findReturnUrl()) !== null) {
            $data['returnUrl'] = strip_tags($returnUrl);
        }

        if (! $this->request->has('install')) {
            // Figure out what needs to be updated, if any
            $data['migrate'] = $this->updates->pendingMigrationHandles();

            return $data;
        }

        $packageNames = $this->request->validate([
            'packageNames' => ['required', 'array'],
            'packageNames.*' => ['string'],
        ])['packageNames'];

        $data = array_merge($data, [
            'install' => $this->parseInstallParam($this->request->get('install')),
            'current' => [],
            'requirements' => [],
            'reverted' => false,
        ]);

        // Convert update handles to Composer package names, and capture current versions
        foreach ($data['install'] as $handle => $version) {
            $packageName = strip_tags($packageNames[$handle]);

            if ($handle === 'craft') {
                $oldPackageName = 'craftcms/cms';
                $current = Craft::$app->getVersion();
            } else {
                $pluginInfo = $this->plugins->getPluginInfo($handle);
                $oldPackageName = $pluginInfo['packageName'];
                $current = $pluginInfo['version'];
            }

            $data['current'][$packageName] = $current;
            $data['requirements'][$packageName] = $version;

            // Has the package name changed?
            if ($packageName !== $oldPackageName) {
                $data['requirements'][$oldPackageName] = false;
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialState(bool $force = false): array
    {
        // Is there anything to install/update?
        if (empty($this->data['install']) && empty($this->data['migrate'])) {
            return $this->finishedState([
                'status' => Craft::t('app', 'Nothing to update.'),
            ]);
        }

        // Is Craft already in Maintenance Mode?
        if (! $force && Craft::$app->getIsInMaintenanceMode()) {
            // Bail if Craft is already in maintenance mode
            return [
                'error' => str_replace(['<br>', '<br/>'], "\n\n", Craft::t('app', 'It looks like someone is currently performing a system update.<br>Only continue if you’re sure that’s not the case.')),
                'options' => [
                    $this->actionOption(Craft::t('app', 'Continue'), self::ACTION_FORCE_UPDATE, ['submit' => true]),
                ],
            ];
        }

        // If there's anything to install, make sure we can find composer.json
        if (! empty($this->data['install']) && ! $this->ensureComposerJson()) {
            return $this->noComposerJsonState();
        }

        // Enable maintenance mode
        Craft::$app->enableMaintenanceMode();

        if (! empty($this->data['install'])) {
            $nextAction = self::ACTION_COMPOSER_INSTALL;
        } else {
            $backup = $this->generalConfig->getBackupOnUpdate();
            $nextAction = $backup ? self::ACTION_BACKUP : self::ACTION_MIGRATE;
        }

        return $this->actionState($nextAction);
    }

    /**
     * {@inheritdoc}
     */
    protected function postComposerInstallState(): array
    {
        // Was this after a revert?
        if ($this->data['reverted']) {
            return $this->actionState(self::ACTION_FINISH, [
                'status' => Craft::t('app', 'The update was reverted successfully.'),
            ]);
        }

        return $this->actionState(self::ACTION_SERVER_CHECK);
    }

    /**
     * Returns the return URL that should be passed with a finished state.
     */
    protected function returnUrl(): string
    {
        return $this->data['returnUrl'] ?? $this->generalConfig->getPostCpLoginRedirect();
    }

    /**
     * {@inheritdoc}
     */
    protected function actionStatus(string $action): string
    {
        return match ($action) {
            self::ACTION_FORCE_UPDATE => Craft::t('app', 'Updating…'),
            self::ACTION_BACKUP => Craft::t('app', 'Backing-up database…'),
            self::ACTION_MIGRATE => Craft::t('app', 'Updating database…'),
            self::ACTION_REVERT => Craft::t('app', 'Reverting update (this may take a minute)…'),
            self::ACTION_SERVER_CHECK => Craft::t('app', 'Checking server requirements…'),
            default => parent::actionStatus($action),
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function sendFinished(array $state = []): Response
    {
        // Disable maintenance mode
        Craft::$app->disableMaintenanceMode();

        return parent::sendFinished($state);
    }

    /**
     * Parses the 'install` param and returns handle => version pairs.
     */
    private function parseInstallParam(array $installParam): array
    {
        $install = [];

        foreach ($installParam as $handle => $version) {
            $handle = strip_tags($handle);
            $version = strip_tags($version);

            if ($this->canUpdate($handle, $version)) {
                $install[$handle] = $version;
            }
        }

        return $install;
    }

    /**
     * Returns whether Craft/a plugin can be updated to a given version.
     */
    private function canUpdate(string $handle, string $toVersion): bool
    {
        if ($handle === 'craft') {
            $fromVersion = Craft::$app->getVersion();
        } else {
            $pluginInfo = null;
            try {
                $pluginInfo = $this->plugins->getPluginInfo($handle);
            } catch (InvalidPluginException) {
            }

            if ($pluginInfo === null || ! $pluginInfo['isInstalled']) {
                throw new BadRequestHttpException('Invalid update handle: '.$handle);
            }

            $fromVersion = $pluginInfo['version'];
        }

        // Normalize the versions in case only one of them starts with a 'v' or something
        $toVersion = normalizeVersion($toVersion);
        $fromVersion = normalizeVersion($fromVersion);

        return Comparator::greaterThan($toVersion, $fromVersion);
    }
}
