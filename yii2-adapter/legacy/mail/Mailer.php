<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail;

use Craft;
use craft\config\GeneralConfig;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Email\Data\EmailSettings;
use CraftCms\Cms\Email\Data\MailSettings;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\SystemMessage\Actions\FormatSystemMessageMailAction;
use CraftCms\Cms\SystemMessage\Actions\RenderSystemMessageAction;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use yii\base\InvalidConfigException;
use yii\mail\MailEvent;

/**
 * The Mailer component provides APIs for sending email in Craft.
 * An instance of the Mailer component is globally accessible in Craft via [[\craft\web\Application::mailer|`Craft::$app->mailer`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use Laravel mailers/drivers and system-message mailables.
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
     * @deprecated 6.0.0 use a Laravel mailable view instead.
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
     * @deprecated 6.0.0 Use the email settings in Settings → Email instead.
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
            throw new InvalidConfigException('Mailer must be configured to create messages with craft\\mail\\Message (or a subclass).');
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

        if ($this->template) {
            Deprecator::log(
                'craft\\mail\\Mailer::$template',
                '`craft\\mail\\Mailer::$template` is deprecated. Set the template via the email settings in Settings → Email instead.',
            );
        }

        if (!empty($this->siteOverrides)) {
            Deprecator::log(
                'craft\\mail\\Mailer::$siteOverrides',
                '`craft\\mail\\Mailer::$siteOverrides` is deprecated. Use the email settings in Settings → Email instead.',
            );
        }

        $generalConfig = Cms::config();
        $currentSite = $messageSite = $twig = null;
        $language = app()->getLocale();
        $generateTransformsBeforePageLoad = $generalConfig->generateTransformsBeforePageLoad;

        $originalTemplateMode = TemplateMode::get();
        TemplateMode::set(TemplateMode::Site);

        try {
            if ($message instanceof Message && isset($message->siteId)) {
                $currentSite = Sites::getCurrentSite();
                if ($message->siteId !== $currentSite->id) {
                    $messageSite = Sites::getSiteById($message->siteId);
                    if ($messageSite) {
                        Sites::setCurrentSite($messageSite);
                        // Reset Twig so any global sets and singles get reloaded for the new site.
                        $twig = Twig::get();
                        Twig::set(Twig::create());
                    }
                }
            }

            if ($message instanceof Message && $message->key !== null) {
                if ($message->language === null) {
                    // If a site was specified, go with its language.
                    if ($messageSite) {
                        $message->language = $messageSite->getLanguage();
                    } else {
                        // Default to the current language.
                        $message->language = Craft::$app->getRequest()->getIsSiteRequest()
                            ? app()->getLocale()
                            : Sites::getPrimarySite()->getLanguage();
                    }
                }

                $mailable = new SystemMessageMailable(
                    key: $message->key,
                    variables: $message->variables ?? [],
                    language: $message->language,
                    siteId: $message->siteId,
                );

                $rendered = app(RenderSystemMessageAction::class)->handle(
                    key: $mailable->key,
                    variables: $mailable->variables,
                    language: $mailable->language,
                    siteId: $mailable->siteId,
                );

                $resolved = $this->resolveMailSettings($message->siteId);

                $formatted = app(FormatSystemMessageMailAction::class)->handle($rendered, $resolved);

                $message->language = $rendered->language;
                $message->setSubject($rendered->subject);
                $message->setTextBody(view('mail.system-message-text', $formatted->viewData)->render());
                $message->setHtmlBody($formatted->htmlBody);
            }

            // Set the default sender if there isn't one already.
            if (!$message->getFrom()) {
                $message->setFrom($this->from);
            }

            if ($this->replyTo && !$message->getReplyTo()) {
                $message->setReplyTo($this->replyTo);
            }

            // Apply the testToEmailAddress config setting.
            if ($generalConfig instanceof GeneralConfig) {
                $testToEmailAddress = $generalConfig->getTestToEmailAddress();
                if (!empty($testToEmailAddress)) {
                    $message->setTo($testToEmailAddress);
                    $message->setCc([]);
                    $message->setBcc([]);
                }
            }

            return parent::send($message);
        } finally {
            // Set things back to normal.
            app()->setLocale($language);
            $generalConfig->generateTransformsBeforePageLoad = $generateTransformsBeforePageLoad;

            if ($currentSite && $messageSite) {
                Sites::setCurrentSite($currentSite);
            }

            TemplateMode::set($originalTemplateMode);

            if ($twig) {
                Twig::set($twig);
            }
        }
    }

    /**
     * Resolves mail settings by merging project config email settings
     * with any legacy template/siteOverrides properties set on the mailer.
     */
    private function resolveMailSettings(?int $siteId = null): MailSettings
    {
        $settings = EmailSettings::fromProjectConfig();

        // Forward legacy $this->template onto the email settings
        if ($this->template) {
            $settings = new EmailSettings(
                fromEmail: $settings->fromEmail,
                fromName: $settings->fromName,
                replyToEmail: $settings->replyToEmail,
                mailer: $settings->mailer,
                template: $this->template,
                siteOverrides: $settings->siteOverrides,
            );
        }

        // Forward legacy $this->siteOverrides onto the email settings
        if (!empty($this->siteOverrides)) {
            $mergedOverrides = $settings->siteOverrides;

            foreach ($this->siteOverrides as $siteUid => $overrideData) {
                $existing = $mergedOverrides[$siteUid] ?? new MailSettings();

                $mergedOverrides[$siteUid] = new MailSettings(
                    fromEmail: $overrideData['fromEmail'] ?? $existing->fromEmail,
                    fromName: $overrideData['fromName'] ?? $existing->fromName,
                    replyToEmail: $overrideData['replyToEmail'] ?? $existing->replyToEmail,
                    template: $overrideData['template'] ?? $existing->template,
                );
            }

            $settings = new EmailSettings(
                fromEmail: $settings->fromEmail,
                fromName: $settings->fromName,
                replyToEmail: $settings->replyToEmail,
                mailer: $settings->mailer,
                template: $settings->template,
                siteOverrides: $mergedOverrides,
            );
        }

        return $settings->resolveForSite($siteId);
    }

    /**
     * @inheritdoc
     */
    protected function sendMessage($message): bool
    {
        try {
            app('mail.manager')->mailer()->getSymfonyTransport()->send($message->getSymfonyEmail());

            return true;
        } catch (TransportExceptionInterface $e) {
            if ($message instanceof Message) {
                $message->error = $e;
            }

            throw $e;
        }
    }
}
