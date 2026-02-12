<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail;

use Craft;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\helpers\App;
use craft\helpers\Template;
use craft\models\Site;
use craft\web\View;
use Throwable;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;
use yii\mail\MailEvent;

/**
 * The Mailer component provides APIs for sending email in Craft.
 * An instance of the Mailer component is globally accessible in Craft via [[\craft\web\Application::mailer|`Craft::$app->mailer`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Mailer extends \yii\symfonymailer\Mailer
{
    /**
     * @event MailEvent The event that is triggered before a message is prepped to be sent.
     * @since 3.6.5
     */
    public const EVENT_BEFORE_PREP = 'beforePrep';

    /**
     * @var string|null The email template that should be used
     */
    public ?string $template = null;

    /**
     * @var string|array|User|User[]|null The default sender’s email address, or their user model(s).
     */
    public User|string|array|null $from = null;

    /**
     * @var string|array|User|User[]|null The default Reply-To email address, or their user model(s).
     * @since 3.4.0
     */
    public User|string|array|null $replyTo = null;

    /**
     * @var array Site overrides
     * @since 5.6.0
     */
    public array $siteOverrides = [];

    /**
     * Composes a new email based on a given key.
     *
     * Craft has four predefined email keys: account_activation, verify_new_email, forgot_password, and test_email.
     *
     * ```php
     * $mailer = Craft::$app->getMailer();
     *
     * $message = $mailer->composeFromKey('account_activation', [
     *     'link' => $activationUrl
     * ]);
     *
     * $mailer->send($message);
     * ```
     *
     * Plugins can register additional emails using the [[\craft\services\SystemMessages::EVENT_REGISTER_MESSAGES]] event.
     *
     * @param string $key The email key
     * @param array $variables Any variables that should be passed to the email body template
     * @return Message The new email message
     * @throws InvalidConfigException if [[messageConfig]] or [[class]] is not configured to use [[Message]]
     */
    public function composeFromKey(string $key, array $variables = []): Message
    {
        $message = $this->createMessage();

        if (!$message instanceof Message) {
            throw new InvalidConfigException('Mailer must be configured to create messages with craft\mail\Message (or a subclass).');
        }

        $message->key = $key;
        $message->variables = $variables;

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function send($message): bool
    {
        // Fire a 'beforePrep' event
        $this->trigger(self::EVENT_BEFORE_PREP, new MailEvent([
            'message' => $message,
        ]));

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $sitesService = Craft::$app->getSites();
        $view = Craft::$app->getView();
        $currentSite = $messageSite = $twig = null;
        $language = Craft::$app->language;
        $generateTransformsBeforePageLoad = $generalConfig->generateTransformsBeforePageLoad;
        $originalSettings = [];

        $originalTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        try {
            if ($message instanceof Message && isset($message->siteId)) {
                $currentSite = $sitesService->getCurrentSite();
                if ($message->siteId !== $currentSite->id) {
                    $messageSite = $sitesService->getSiteById($message->siteId);
                    if ($messageSite) {
                        $sitesService->setCurrentSite($messageSite);
                        // reset Twig so any global sets and singles get reloaded for the new site
                        $twig = $view->getTwig();
                        $view->setTwig($view->createTwig());
                    }
                }
            }

            $overrides = $this->siteOverrides[$sitesService->getCurrentSite()->uid] ?? [];
            if (isset($overrides['fromEmail']) || isset($overrides['fromName'])) {
                $originalSettings['from'] = $this->from;
                $fromEmail = $overrides['fromEmail'] ?? array_key_first($this->from);
                $fromName = $overrides['fromName'] ?? reset($this->from);
                /** @phpstan-ignore-next-line */
                $this->from = [
                    App::parseEnv($fromEmail) => App::parseEnv($fromName),
                ];
            }
            if (isset($overrides['replyToEmail'])) {
                $originalSettings['replyTo'] = $this->replyTo;
                $this->replyTo = App::parseEnv($overrides['replyToEmail']);
            }
            if (isset($overrides['template'])) {
                $originalSettings['template'] = $this->template;
                $this->template = App::parseEnv($overrides['template']);
            }

            if ($message instanceof Message && $message->key !== null) {
                if ($message->language === null) {
                    // If a site was specified, go with its language
                    if ($messageSite) {
                        $message->language = $messageSite->language;
                    } else {
                        // Default to the current language
                        $message->language = Craft::$app->getRequest()->getIsSiteRequest()
                            ? Craft::$app->language
                            : Craft::$app->getSites()->getPrimarySite()->language;
                    }
                }

                // Use the message language
                Craft::$app->language = $message->language;

                // Temporarily disable lazy transform generation
                $generalConfig->generateTransformsBeforePageLoad = true;

                $systemMessage = Craft::$app->getSystemMessages()->getMessage($message->key, $message->language);

                $settings = App::mailSettings();
                $variables = ($message->variables ?: []) + [
                        'emailKey' => $message->key,
                        'fromEmail' => App::parseEnv($settings->fromEmail),
                        'replyToEmail' => App::parseEnv($settings->replyToEmail),
                        'fromName' => App::parseEnv($settings->fromName),
                        'language' => $message->language,
                    ];

                // Render the subject and body text
                $subject = $view->renderSandboxedString($systemMessage->subject, $variables);
                $textBody = $view->renderSandboxedString($systemMessage->body, $variables);
                $htmlBody = $view->renderSandboxedString($systemMessage->body, $variables, escapeHtml: true);

                // Remove </> from around URLs, so they’re not interpreted as HTML tags
                $textBody = preg_replace('/<(https?:\/\/.+?)>/', '$1', $textBody);

                $message->setSubject($subject);
                $message->setTextBody($textBody);

                // Is there a custom HTML template set?
                if (Craft::$app->edition->value >= CmsEdition::Pro->value && $this->template) {
                    $template = $this->template;
                    $templateMode = View::TEMPLATE_MODE_SITE;
                } else {
                    // Default to the _special/email.html template
                    $template = '_special/email.twig';
                    $templateMode = View::TEMPLATE_MODE_CP;
                }

                try {
                    $message->setHtmlBody($view->renderTemplate($template, array_merge($variables, [
                        'body' => Template::raw(Markdown::process($htmlBody, 'gfm')),
                    ]), $templateMode));
                } catch (Throwable $e) {
                    // Just log it and don't worry about the HTML body
                    Craft::warning('Error rendering email template: ' . $e->getMessage(), __METHOD__);
                    Craft::$app->getErrorHandler()->logException($e);
                }
            }

            // Set the default sender if there isn't one already
            if (!$message->getFrom()) {
                $message->setFrom($this->from);
            }

            if ($this->replyTo && !$message->getReplyTo()) {
                $message->setReplyTo($this->replyTo);
            }

            // Apply the testToEmailAddress config setting
            $testToEmailAddress = $generalConfig->getTestToEmailAddress();
            if (!empty($testToEmailAddress)) {
                $message->setTo($testToEmailAddress);
                $message->setCc([]);
                $message->setBcc([]);
            }

            return parent::send($message);
        } finally {
            // Set things back to normal
            Craft::$app->language = $language;
            $generalConfig->generateTransformsBeforePageLoad = $generateTransformsBeforePageLoad;

            if ($currentSite && $messageSite) {
                $sitesService->setCurrentSite($currentSite);
            }

            $view->setTemplateMode($originalTemplateMode);

            if ($twig) {
                $view->setTwig($twig);
            }

            Craft::configure($this, $originalSettings);
        }
    }
}
