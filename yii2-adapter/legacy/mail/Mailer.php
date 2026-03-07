<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Twig;
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
     * @deprecated 6.0.0 configure Laravel mailers per environment instead.
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
                '`craft\\mail\\Mailer::$template` is deprecated and no longer has any effect. Use a Laravel mailable view instead.',
            );
        }

        if (!empty($this->siteOverrides)) {
            Deprecator::log(
                'craft\\mail\\Mailer::$siteOverrides',
                '`craft\\mail\\Mailer::$siteOverrides` is deprecated and no longer has any effect. Configure Laravel mailers per environment instead.',
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

                $message->language = $rendered->language;
                $message->setSubject($rendered->subject);
                $message->setTextBody(view('mail.system-message-text', [
                    'textBody' => $rendered->textBody,
                    'variables' => $rendered->variables,
                ])->render());
                $message->setHtmlBody($rendered->htmlBody);
            }

            // Set the default sender if there isn't one already.
            if (!$message->getFrom()) {
                $message->setFrom($this->from);
            }

            if ($this->replyTo && !$message->getReplyTo()) {
                $message->setReplyTo($this->replyTo);
            }

            // Apply the testToEmailAddress config setting.
            if ($generalConfig instanceof \craft\config\GeneralConfig) {
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
