<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\mail;

use Craft;
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
    // Properties
    // =========================================================================

    /**
     * @var string The default message class name
     */
    public $messageClass = 'craft\app\mail\Message';

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
     * [registerEmailMessages](http://buildwithcraft.com/docs/plugins/hooks-reference#registerEmailMessages) hook, and
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

            $tempTemplatesPath = '';

            if (Craft::$app->getEdition() >= Craft::Client) {
                // Is there a custom HTML template set?
                if ($this->template !== null) {
                    $tempTemplatesPath = Craft::$app->getPath()->getSiteTemplatesPath();
                    $parentTemplate = $this->template;
                }
            }

            if (empty($parentTemplate)) {
                $tempTemplatesPath = Craft::$app->getPath()->getCpTemplatesPath();
                $parentTemplate = '_special/email';
            }

            // Temporarily swap the templates path
            $originalTemplatesPath = Craft::$app->getPath()->getTemplatesPath();
            Craft::$app->getPath()->setTemplatesPath($tempTemplatesPath);

            $htmlBodyTemplate = "{% extends '{$parentTemplate}' %}\n" .
                "{% set body %}\n" .
                Markdown::process($textBodyTemplate) .
                "{% endset %}\n";

            $variables = $message->variables ?: [];
            $variables['emailKey'] = $message->key;

            $message->setSubject(Craft::$app->getView()->renderString($subjectTemplate, $variables));
            $message->setTextBody(Craft::$app->getView()->renderString($textBodyTemplate, $variables));
            $message->setHtmlBody(Craft::$app->getView()->renderString($htmlBodyTemplate, $variables));

            // Return to the original templates path
            Craft::$app->getPath()->setTemplatesPath($originalTemplatesPath);
        }

        // Set the default sender if there isn't one already
        if (!$message->getFrom()) {
            $message->setFrom($this->from);
        }

        return parent::send($message);
    }
}
