<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\migrations\Install;
use craft\models\Site;
use craft\web\assets\installer\InstallerAsset;
use craft\web\Controller;
use yii\base\Response;
use yii\web\BadRequestHttpException;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The InstallController class is a controller that directs all installation related tasks such as creating the database
 * schema and default content for a Craft installation.
 *
 * Note that all actions in the controller are open to do not require an authenticated Craft session in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class InstallController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @throws BadRequestHttpException if Craft is already installed
     */
    public function init()
    {
        // Return a 404 if Craft is already installed
        if (!YII_DEBUG && Craft::$app->getIsInstalled()) {
            throw new BadRequestHttpException('Craft CMS is already installed');
        }
    }

    /**
     * Index action.
     *
     * @return Response|string The requirements check response if the server doesn’t meet Craft’s requirements, or the rendering result
     * @throws \Throwable if it's an Ajax request and the server doesn’t meet Craft’s requirements
     */
    public function actionIndex()
    {
        if (($response = Craft::$app->runAction('templates/requirements-check')) !== null) {
            return $response;
        }

        // Guess the site name based on the server name
        $server = Craft::$app->getRequest()->getServerName();
        $words = preg_split('/[\-_\.]+/', $server);
        array_pop($words);

        $vars = [];
        $vars['defaultSystemName'] = implode(' ', array_map('ucfirst', $words));
        $vars['defaultSiteUrl'] = 'http://'.$server;

        $this->getView()->registerAssetBundle(InstallerAsset::class);

        return $this->renderTemplate('_special/install', $vars);
    }

    /**
     * Validates the user account credentials.
     *
     * @return Response
     */
    public function actionValidateAccount(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $user = new User(['scenario' => User::SCENARIO_REGISTRATION]);
        $request = Craft::$app->getRequest();
        $user->email = $request->getBodyParam('email');
        $user->username = $request->getBodyParam('username', $user->email);
        $user->newPassword = $request->getBodyParam('password');
        $return = [];

        if ($user->validate()) {
            $return['validates'] = true;
        } else {
            $errors = $user->getErrors();
            if (isset($errors['newPassword'])) {
                $errors['password'] = ArrayHelper::remove($errors, 'newPassword');
            }
            $return['errors'] = $errors;
        }

        return $this->asJson($return);
    }

    /**
     * Validates the site settings.
     *
     * @return Response
     */
    public function actionValidateSite(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $site = new Site();
        $site->name = $request->getBodyParam('systemName');
        $site->handle = 'default';
        $site->baseUrl = $request->getBodyParam('siteUrl');
        $site->language = $request->getBodyParam('siteLanguage');
        $return = [];

        if ($site->validate()) {
            $return['validates'] = true;
        } else {
            $return['errors'] = $site->getErrors();
        }

        return $this->asJson($return);
    }

    /**
     * Install action.
     *
     * @return Response
     */
    public function actionInstall(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        // Run the install migration
        $request = Craft::$app->getRequest();
        $migrator = Craft::$app->getMigrator();

        $email = $request->getBodyParam('email');
        $username = $request->getBodyParam('username', $email);

        $site = new Site([
            'name' => $request->getBodyParam('systemName'),
            'handle' => 'default',
            'hasUrls' => true,
            'baseUrl' => $request->getBodyParam('siteUrl'),
            'language' => $request->getBodyParam('siteLanguage'),
        ]);

        $migration = new Install([
            'username' => $username,
            'password' => $request->getBodyParam('password'),
            'email' => $email,
            'site' => $site,
        ]);

        if ($migrator->migrateUp($migration) !== false) {
            $success = true;

            // Mark all existing migrations as applied
            foreach ($migrator->getNewMigrations() as $name) {
                $migrator->addMigrationHistory($name);
            }
        } else {
            $success = false;
        }

        return $this->asJson(['success' => $success]);
    }
}
