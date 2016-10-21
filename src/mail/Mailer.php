<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\mail;

use Craft;
use craft\app\elements\User;
use craft\app\errors\SendEmailException;
use craft\app\events\MailFailureEvent;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;

/**
 * The Mailer component provides APIs for sending email in Craft.
 *
 * An instance of the Email service is globally accessible in Craft via [[Application::email `Craft::$app->getMailer()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Mailer extends \yii\swiftmailer\Mailer
{
    // Constants
    // =========================================================================

    /**
     * @event MailFailureEvent The event that is triggered when there is an error sending an email.
     */
    const EVENT_SEND_MAIL_FAILURE = 'sendMailFailure';

    // Properties
    // =========================================================================

    /**
     * @var string The default message class name
     */
    public $messageClass = \craft\app\mail\Message::class;

    /**
     * @var string The email template that should be used
     */
    public $template;

    /**
     * @var string|array|User|User[] $from The default sender’s email address, or their user model(s).
     */
    public $from;

    // Public Methods
    // =========================================================================

    /**
     * Composes a new email based on a given key.
     *
     * Craft has four predefined email keys: account_activation, verify_new_email, forgot_password, and test_email.
     *
     * Plugins can register additional email keys using the
     * [registerEmailMessages](http://craftcms.com/docs/plugins/hooks-reference#registerEmailMessages) hook, and
     * by providing the corresponding language strings.
     *
     * ```php
     * Craft::$app->getMailer()->composeFromKey('account_activation', [
     *     'link' => $activationUrl
     * ]);
     * ```
     *
     * @param string $key       The email key
     * @param array  $variables Any variables that should be passed to the email body template
     *
     * @return Message The new email message
     * @throws InvalidConfigException if [[messageConfig]] or [[class]] is not configured to use [[Message]]
     */
    public function composeFromKey($key, $variables = [])
    {
        $message = $this->createMessage();

        if (!$message instanceof Message) {
            throw new InvalidConfigException('Mailer must be configured to create messages with craft\app\mail\Message (or a subclass).');
        }

        $message->key = $key;
        $message->variables = $variables;

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function send($message)
    {
        if ($message instanceof Message && $message->key !== null) {
            if (Craft::$app->getEdition() >= Craft::Client) {
                $storedMessage = Craft::$app->getEmailMessages()->getMessage($message->key, $message->language);

                $subjectTemplate = $storedMessage->subject;
                $textBodyTemplate = $storedMessage->body;
            } else {
                $subjectTemplate = Craft::t('app', $message->key.'_subject', null, 'en_us');
                $textBodyTemplate = Craft::t('app', $message->key.'_body', null, 'en_us');
            }

            $view = Craft::$app->getView();
            $oldTemplateMode = $view->getTemplateMode();

            if (Craft::$app->getEdition() >= Craft::Client) {
                // Is there a custom HTML template set?
                if ($this->template !== null) {
                    $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
                    $parentTemplate = $this->template;
                }
            }

            if (empty($parentTemplate)) {
                // Default to the _special/email.html template
                $view->setTemplateMode($view::TEMPLATE_MODE_CP);
                $parentTemplate = '_special/email';
            }

            $htmlBodyTemplate = "{% extends '{$parentTemplate}' %}\n".
                "{% set body %}\n".
                Markdown::process($textBodyTemplate).
                "{% endset %}\n";

            $variables = $message->variables ?: [];
            $variables['emailKey'] = $message->key;

            // Do we need to temporarily swap the app language?
            $language = Craft::$app->language;

            if ($message->language !== null) {
                Craft::$app->language = $message->language;
            }

            $message->setSubject(Craft::$app->getView()->renderString($subjectTemplate, $variables));
            $message->setHtmlBody(Craft::$app->getView()->renderString($htmlBodyTemplate, $variables));

            // Don't let Twig use the HTML escaping strategy on the plain text portion body of the email.
            Craft::$app->getView()->getTwig()->getExtension('escaper')->setDefaultStrategy(false);
            $message->setTextBody(Craft::$app->getView()->renderString($textBodyTemplate, $variables));
            Craft::$app->getView()->getTwig()->getExtension('escaper')->setDefaultStrategy('html');

            Craft::$app->language = $language;

            // Return to the original template mode
            $view->setTemplateMode($oldTemplateMode);
        }

        // Set the default sender if there isn't one already
        if (!$message->getFrom()) {
            $message->setFrom($this->from);
        }

        try {
            $isSuccessful = parent::send($message);
            $error = null;
        } catch (\Exception $e) {
            $isSuccessful = false;
            $error = $e->getMessage();
        }

        // Either an exception was thrown or parent::send() returned false.
        if ($isSuccessful === false) {
            // Fire a 'sendMailFailure' event
            $this->trigger(self::EVENT_SEND_MAIL_FAILURE, new MailFailureEvent([
                'user' => $message->getTo(),
                'email' => $message,
                'variables' => $message->variables,
                'error' => $error,
            ]));

            throw new SendEmailException('Error sending email: '.($error ?: 'Unknown'));
        }

        return $isSuccessful;
    }
}
