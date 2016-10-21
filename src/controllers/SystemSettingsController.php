<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\dates\DateTime;
use craft\app\errors\MissingComponentException;
use craft\app\helpers\MailerHelper;
use craft\app\helpers\Component;
use craft\app\helpers\Template;
use craft\app\helpers\Url;
use craft\app\mail\transportadapters\BaseTransportAdapter;
use craft\app\mail\transportadapters\Gmail;
use craft\app\mail\transportadapters\Php;
use craft\app\mail\transportadapters\Sendmail;
use craft\app\mail\transportadapters\Smtp;
use craft\app\mail\transportadapters\TransportAdapterInterface;
use craft\app\models\MailSettings;
use craft\app\elements\GlobalSet;
use craft\app\models\Info;
use craft\app\tools\AssetIndex;
use craft\app\tools\ClearCaches;
use craft\app\tools\DbBackup;
use craft\app\tools\FindAndReplace;
use craft\app\tools\SearchIndex;
use craft\app\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The SystemSettingsController class is a controller that handles various control panel settings related tasks such as
 * displaying, saving and testing Craft settings in the control panel.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SystemSettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All system setting actions require an admin
        $this->requireAdmin();
    }

    /**
     * Shows the settings index.
     *
     * @return string The rendering result
     */
    public function actionSettingsIndex()
    {
        $tools = [];

        // Only include the Update Asset Indexes tool if there are any asset volumes
        if (count(Craft::$app->getVolumes()->getAllVolumes()) !== 0) {
            $tools[] = new AssetIndex();
        }

        $tools[] = new ClearCaches();
        $tools[] = new DbBackup();
        $tools[] = new FindAndReplace();
        $tools[] = new SearchIndex();

        return $this->renderTemplate('settings/_index', [
            'tools' => $tools
        ]);
    }

    /**
     * Shows the general settings form.
     *
     * @param Info $info The info being edited, if there were any validation errors.
     *
     * @return string The rendering result
     */
    public function actionGeneralSettings(Info $info = null)
    {
        if ($info === null) {
            $info = Craft::$app->getInfo();
        }

        // Assemble the timezone options array (Technique adapted from http://stackoverflow.com/a/7022536/1688568)
        $timezoneOptions = [];

        $utc = new DateTime();
        $offsets = [];
        $timezoneIds = [];
        $includedAbbrs = [];

        foreach (\DateTimeZone::listIdentifiers() as $timezoneId) {
            $timezone = new \DateTimeZone($timezoneId);
            $transition = $timezone->getTransitions($utc->getTimestamp(), $utc->getTimestamp());
            $abbr = $transition[0]['abbr'];

            $offset = round($timezone->getOffset($utc) / 60);

            if ($offset) {
                $hour = floor($offset / 60);
                $minutes = floor(abs($offset) % 60);

                $format = sprintf('%+d', $hour);

                if ($minutes) {
                    $format .= ':'.sprintf('%02u', $minutes);
                }
            } else {
                $format = '';
            }

            $offsets[] = $offset;
            $timezoneIds[] = $timezoneId;
            $includedAbbrs[] = $abbr;
            $timezoneOptions[] = [
                'value' => $timezoneId,
                'label' => 'UTC'.$format.($abbr != 'UTC' ? " ({$abbr})" : '').($timezoneId != 'UTC' ? ' – '.$timezoneId : '')
            ];
        }

        array_multisort($offsets, $timezoneIds, $timezoneOptions);

        return $this->renderTemplate('settings/general/_index', [
            'info' => $info,
            'timezoneOptions' => $timezoneOptions
        ]);
    }

    /**
     * Saves the general settings.
     *
     * @return Response|null
     */
    public function actionSaveGeneralSettings()
    {
        $this->requirePostRequest();

        $info = Craft::$app->getInfo();

        $info->on = (bool)Craft::$app->getRequest()->getBodyParam('on');
        $info->timezone = Craft::$app->getRequest()->getBodyParam('timezone');

        if (Craft::$app->saveInfo($info)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'General settings saved.'));

            return $this->redirectToPostedUrl();
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save general settings.'));

        // Send the info back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'info' => $info
        ]);


        return null;
    }

    /**
     * Renders the email settings page.
     *
     * @param MailSettings|null         $settings The posted email settings, if there were any validation errors
     * @param TransportAdapterInterface $adapter  The transport adapter, if there were any validation errors
     *
     * @return Response
     */
    public function actionEditEmailSettings(MailSettings $settings = null, TransportAdapterInterface $adapter = null)
    {
        if ($settings === null) {
            $settings = Craft::$app->getSystemSettings()->getEmailSettings();
        }

        if ($adapter === null) {
            $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);

            if ($adapter === false) {
                // Fallback to the PHP mailer
                $adapter = new Php();
            }
        }

        /** @var TransportAdapterInterface[] $allTransportTypes */
        $allTransportTypes = [
            new Php(),
            new Sendmail(),
            new Smtp(),
            new Gmail(),
        ];

        foreach (Craft::$app->getPlugins()->call('getMailTransportAdapters', [], true) as $pluginTransportTypes) {
            foreach ($pluginTransportTypes as $pluginTransportType) {
                if (is_object($pluginTransportType)) {
                    if ($pluginTransportType instanceof TransportAdapterInterface) {
                        $allTransportTypes[] = $pluginTransportType;
                    }
                } else {
                    $pluginTransportType = MailerHelper::createTransportAdapter($pluginTransportType);
                    if ($pluginTransportType !== false) {
                        $allTransportTypes[] = $pluginTransportType;
                    }
                }
            }
        }

        $transportTypeOptions = [];

        foreach ($allTransportTypes as $class) {
            if ($class::className() === get_class($adapter) || $class::isSelectable()) {
                $transportTypeOptions[] = [
                    'value' => $class::className(),
                    'label' => $class::displayName()
                ];
            }
        }

        return $this->renderTemplate('settings/email/_index', [
            'settings' => $settings,
            'adapter' => $adapter,
            'transportTypeOptions' => $transportTypeOptions,
            'allTransportTypes' => $allTransportTypes,
        ]);
    }

    /**
     * Saves the email settings.
     *
     * @return Response|null
     */
    public function actionSaveEmailSettings()
    {
        $this->requirePostRequest();

        $settings = $this->_createMailSettingsFromPost();
        $settingsIsValid = $settings->validate();

        /** @var BaseTransportAdapter $adapter */
        $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);
        $adapterIsValid = $adapter->validate();

        if ($settingsIsValid && $adapterIsValid) {
            Craft::$app->getSystemSettings()->saveSettings('email', $settings->toArray());
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Email settings saved.'));

            return $this->redirectToPostedUrl();
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save email settings.'));

        // Send the settings back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'settings' => $settings,
            'adapter' => $adapter
        ]);

        return null;
    }

    /**
     * Tests the email settings.
     *
     * @return null
     */
    public function actionTestEmailSettings()
    {
        $this->requirePostRequest();

        $settings = $this->_createMailSettingsFromPost();
        $settingsIsValid = $settings->validate();

        /** @var BaseTransportAdapter $adapter */
        $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);
        $adapterIsValid = $adapter->validate();

        if ($settingsIsValid && $adapterIsValid) {
            $mailer = MailerHelper::createMailer($settings);

            // Compose the settings list as HTML
            $includedSettings = [];

            foreach (['fromEmail', 'fromName', 'template'] as $name) {
                if (!empty($settings->$name)) {
                    $includedSettings[] = '<strong>'.$settings->getAttributeLabel($name).':</strong> '.$settings->$name;
                }
            }

            $includedSettings[] = '<strong>'.Craft::t('app', 'Transport Type').':</strong> '.$adapter::displayName();

            foreach ($adapter->settingsAttributes() as $name) {
                if (!empty($adapter->$name)) {
                    $includedSettings[] = '<strong>'.$adapter->getAttributeLabel($name).':</strong> '.$adapter->$name;
                }
            }

            $settingsHtml = implode('<br/>', $includedSettings);

            // Try to send the test email
            $message = $mailer
                ->composeFromKey('test_email', ['settings' => Template::getRaw($settingsHtml)])
                ->setTo(Craft::$app->getUser()->getIdentity());

            if ($message->send()) {
                Craft::$app->getSession()->setNotice(Craft::t('app', 'Email sent successfully! Check your inbox.'));
            } else {
                Craft::$app->getSession()->setError(Craft::t('app', 'There was an error testing your email settings.'));
            }
        } else {
            Craft::$app->getSession()->setError(Craft::t('app', 'Your email settings are invalid.'));
        }

        // Send the settings back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'settings' => $settings,
            'adapter' => $adapter
        ]);

        return null;
    }

    /**
     * Global Set edit form.
     *
     * @param integer   $globalSetId The global set’s ID, if any.
     * @param GlobalSet $globalSet   The global set being edited, if there were any validation errors.
     *
     * @return string The rendering result
     * @throws NotFoundHttpException if the requested global set cannot be found
     */
    public function actionEditGlobalSet($globalSetId = null, GlobalSet $globalSet = null)
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
            $title = $globalSet->name;
        } else {
            $title = Craft::t('app', 'Create a new global set');
        }

        // Breadcrumbs
        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => Url::getUrl('settings')
            ],
            [
                'label' => Craft::t('app', 'Globals'),
                'url' => Url::getUrl('settings/globals')
            ]
        ];

        // Tabs
        $tabs = [
            'settings' => [
                'label' => Craft::t('app', 'Settings'),
                'url' => '#set-settings'
            ],
            'fieldlayout' => [
                'label' => Craft::t('app', 'Field Layout'),
                'url' => '#set-fieldlayout'
            ]
        ];

        // Render the template!
        return $this->renderTemplate('settings/globals/_edit', [
            'globalSetId' => $globalSetId,
            'globalSet' => $globalSet,
            'title' => $title,
            'crumbs' => $crumbs,
            'tabs' => $tabs
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Creates a MailSettings model, populated with post data.
     *
     * @return MailSettings
     */
    private function _createMailSettingsFromPost()
    {
        $request = Craft::$app->getRequest();
        $settings = new MailSettings();

        $settings->fromEmail = $request->getBodyParam('fromEmail');
        $settings->fromName = $request->getBodyParam('fromName');
        $settings->template = $request->getBodyParam('template');
        $settings->transportType = $request->getBodyParam('transportType');
        $settings->transportSettings = $request->getBodyParam('transportTypes.'.$settings->transportType);

        return $settings;
    }
}
