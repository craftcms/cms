<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\GlobalSet;
use craft\errors\MissingComponentException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\MailerHelper;
use craft\helpers\UrlHelper;
use craft\mail\Mailer;
use craft\mail\transportadapters\BaseTransportAdapter;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\TransportAdapterInterface;
use craft\models\MailSettings;
use craft\web\assets\generalsettings\GeneralSettingsAsset;
use craft\web\Controller;
use yii\base\Exception;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The SystemSettingsController class is a controller that handles various control panel settings related tasks such as
 * displaying, saving and testing Craft settings in the control panel.
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SystemSettingsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // All system setting actions require an admin
        $this->requireAdmin();

        return parent::beforeAction($action);
    }

    /**
     * Shows the general settings form.
     *
     * @return Response
     */
    public function actionGeneralSettings(): Response
    {
        $this->getView()->registerAssetBundle(GeneralSettingsAsset::class);

        return $this->renderTemplate('settings/general/_index.twig', [
            'system' => Craft::$app->getProjectConfig()->get('system') ?? [],
        ]);
    }

    /**
     * Saves the general settings.
     *
     * @return Response|null
     */
    public function actionSaveGeneralSettings(): ?Response
    {
        $this->requirePostRequest();

        $projectConfig = Craft::$app->getProjectConfig();
        $systemSettings = $projectConfig->get('system');
        $systemSettings['name'] = $this->request->getBodyParam('name');
        $systemSettings['live'] = $this->request->getBodyParam('live');
        $systemSettings['retryDuration'] = (int)$this->request->getBodyParam('retryDuration') ?: null;
        $systemSettings['timeZone'] = $this->request->getBodyParam('timeZone');

        if (!str_starts_with($systemSettings['live'], '$')) {
            $systemSettings['live'] = (bool)$systemSettings['live'];
        }

        $projectConfig->set('system', $systemSettings, 'Update system settings.');

        $this->setSuccessFlash(Craft::t('app', 'General settings saved.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Renders the email settings page.
     *
     * @param MailSettings|null $settings The posted email settings, if there were any validation errors
     * @param TransportAdapterInterface|null $adapter The transport adapter, if there were any validation errors
     * @return Response
     * @throws Exception if a plugin returns an invalid mail transport type
     */
    public function actionEditEmailSettings(?MailSettings $settings = null, ?TransportAdapterInterface $adapter = null): Response
    {
        if ($settings === null) {
            $settings = App::mailSettings();
        }

        if ($adapter === null) {
            try {
                $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);
            } catch (MissingComponentException) {
                $adapter = new Sendmail();
                $adapter->addError('type', Craft::t('app', 'The transport type “{type}” could not be found.', [
                    'type' => $settings->transportType,
                ]));
            }
        }

        // Get all the registered transport adapter types
        $allTransportAdapterTypes = MailerHelper::allMailerTransportTypes();

        // Make sure the selected adapter class is in there
        if (!in_array(get_class($adapter), $allTransportAdapterTypes, true)) {
            $allTransportAdapterTypes[] = get_class($adapter);
        }

        $allTransportAdapters = [];
        $transportTypeOptions = [];

        foreach ($allTransportAdapterTypes as $transportAdapterType) {
            /** @var string|TransportAdapterInterface $transportAdapterType */
            /** @phpstan-var class-string<TransportAdapterInterface>|TransportAdapterInterface $transportAdapterType */
            if ($transportAdapterType === get_class($adapter) || $transportAdapterType::isSelectable()) {
                $allTransportAdapters[] = MailerHelper::createTransportAdapter($transportAdapterType);
                $transportTypeOptions[] = [
                    'value' => $transportAdapterType,
                    'label' => $transportAdapterType::displayName(),
                ];
            }
        }

        // Sort them by name
        ArrayHelper::multisort($transportTypeOptions, 'label');

        // See if it looks like config/app.php is overriding the mailer component
        $customMailerFiles = [];
        $configService = Craft::$app->getConfig();
        foreach (['app', 'app.web', 'app.console'] as $file) {
            $config = $configService->getConfigFromFile($file);
            if (isset($config['components']) && array_key_exists('mailer', $config['components'])) {
                $customMailerFiles[] = $configService->getConfigFilePath($file);
            }
        }

        return $this->renderTemplate('settings/email/_index.twig', [
            'settings' => $settings,
            'adapter' => $adapter,
            'transportTypeOptions' => $transportTypeOptions,
            'allTransportAdapters' => $allTransportAdapters,
            'customMailerFiles' => $customMailerFiles,
        ]);
    }

    /**
     * Saves the email settings.
     *
     * @return Response|null
     */
    public function actionSaveEmailSettings(): ?Response
    {
        $this->requirePostRequest();

        $settings = $this->_createMailSettingsFromPost();
        $settingsAreValid = $settings->validate();

        /** @var BaseTransportAdapter $adapter */
        $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);
        $adapterIsValid = $adapter->validate();

        if (!$settingsAreValid || !$adapterIsValid) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save email settings.'));

            // Send the settings back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
                'adapter' => $adapter,
            ]);

            return null;
        }

        Craft::$app->getProjectConfig()->set('email', $settings->toArray(), 'Update email settings.');

        $this->setSuccessFlash(Craft::t('app', 'Email settings saved.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Tests the email settings.
     */
    public function actionTestEmailSettings(): void
    {
        $this->requirePostRequest();

        $settings = $this->_createMailSettingsFromPost();
        $settingsIsValid = $settings->validate();

        /** @var BaseTransportAdapter $adapter */
        $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);
        $adapterIsValid = $adapter->validate();

        if ($settingsIsValid && $adapterIsValid) {
            // Try to send the test email
            /** @var Mailer $mailer */
            $mailer = Craft::createObject(App::mailerConfig($settings));
            $message = $mailer
                ->composeFromKey('test_email', [
                    'settings' => MailerHelper::settingsReport($mailer, $adapter),
                ])
                ->setTo(static::currentUser());

            if ($message->send()) {
                $this->setSuccessFlash(Craft::t('app', 'Email sent successfully! Check your inbox.'));
            } else {
                $this->setFailFlash(Craft::t('app', 'There was an error testing your email settings.'));
            }
        } else {
            $this->setFailFlash(Craft::t('app', 'Your email settings are invalid.'));
        }

        // Send the settings back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'settings' => $settings,
            'adapter' => $adapter,
        ]);
    }

    /**
     * Global Set edit form.
     *
     * @param int|null $globalSetId The global set’s ID, if any.
     * @param GlobalSet|null $globalSet The global set being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested global set cannot be found
     */
    public function actionEditGlobalSet(?int $globalSetId = null, ?GlobalSet $globalSet = null): Response
    {
        if ($globalSet === null) {
            if ($globalSetId !== null) {
                $globalSet = Craft::$app->getGlobals()->getSetById($globalSetId);

                if (!$globalSet) {
                    throw new NotFoundHttpException('Global set not found');
                }
            } else {
                $globalSet = new GlobalSet();
            }
        }

        if ($globalSet->id) {
            $title = trim($globalSet->name) ?: Craft::t('app', 'Edit Global Set');
        } else {
            $title = Craft::t('app', 'Create a new global set');
        }

        // Breadcrumbs
        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings'),
            ],
            [
                'label' => Craft::t('app', 'Globals'),
                'url' => UrlHelper::url('settings/globals'),
            ],
        ];

        // Render the template!
        return $this->renderTemplate('settings/globals/_edit.twig', [
            'globalSetId' => $globalSetId,
            'globalSet' => $globalSet,
            'title' => $title,
            'crumbs' => $crumbs,
        ]);
    }

    /**
     * Creates a MailSettings model, populated with post data.
     *
     * @return MailSettings
     */
    private function _createMailSettingsFromPost(): MailSettings
    {
        $settings = new MailSettings();

        $settings->fromEmail = $this->request->getBodyParam('fromEmail');
        $settings->replyToEmail = $this->request->getBodyParam('replyToEmail') ?: null;
        $settings->fromName = $this->request->getBodyParam('fromName');
        $settings->template = $this->request->getBodyParam('template');
        $settings->transportType = $this->request->getBodyParam('transportType');
        $settings->transportSettings = $this->request->getBodyParam('transportTypes.' . $settings->transportType);

        return $settings;
    }
}
