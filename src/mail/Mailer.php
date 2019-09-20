<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail;

use Craft;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\Template;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;
use yii\mail\MessageInterface;

/**
 * The Mailer component provides APIs for sending email in Craft.
 * An instance of the Mailer component is globally accessible in Craft via [[\craft\web\Application::mailer|`Craft::$app->mailer`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Mailer extends \yii\swiftmailer\Mailer
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The email template that should be used
     */
    public $template;

    /**
     * @var string|array|User|User[]|null The default sender’s email address, or their user model(s).
     */
    public $from;

    // Public Methods
    // =========================================================================

    /**
     * Composes a new email based on a given key.
     *
     * Craft has four predefined email keys: account_activation, verify_new_email, forgot_password, and test_email.
     * Plugins can register additional email keys using the
     * [registerEmailMessages](http://craftcms.com/docs/plugins/hooks-reference#registerEmailMessages) hook, and
     * by providing the corresponding language strings.
     *
     * ```php
     * Craft::$app->mailer->composeFromKey('account_activation', [
     *     'link' => $activationUrl
     * ]);
     * ```
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
     * Sends the given email message.
     *
     * This method will log a message about the email being sent.
     * If [[useFileTransport]] is true, it will save the email as a file under [[fileTransportPath]].
     * Otherwise, it will call [[sendMessage()]] to send the email to its recipient(s).
     * Child classes should implement [[sendMessage()]] with the actual email sending logic.
     *
     * @param MessageInterface $message The email message instance to be sent.
     * @return bool Whether the message has been sent successfully.
     */
    public function send($message)
    {
        if ($message instanceof Message && $message->key !== null) {
            if ($message->language === null) {
                // Default to the current language
                $message->language = Craft::$app->getRequest()->getIsSiteRequest()
                    ? Craft::$app->language
                    : Craft::$app->getSites()->getPrimarySite()->language;
            }

            $systemMessage = Craft::$app->getSystemMessages()->getMessage($message->key, $message->language);
            $subjectTemplate = $systemMessage->subject;
            $textBodyTemplate = $systemMessage->body;

            // Use the site template mode
            $view = Craft::$app->getView();
            $templateMode = $view->getTemplateMode();
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

            // Use the message language
            $language = Craft::$app->language;
            Craft::$app->language = $message->language;

            $settings = App::mailSettings();
            $variables = ($message->variables ?: []) + [
                    'emailKey' => $message->key,
                    'fromEmail' => Craft::parseEnv($settings->fromEmail),
                    'fromName' => Craft::parseEnv($settings->fromName),
                ];

            // Render the subject and textBody
            $message->setSubject($view->renderString($subjectTemplate, $variables));
            $textBody = $view->renderString($textBodyTemplate, $variables);
            $message->setTextBody($textBody);

            // Is there a custom HTML template set?
            if (Craft::$app->getEdition() === Craft::Pro && $this->template) {
                $template = $this->template;
            } else {
                // Default to the _special/email.html template
                $view->setTemplateMode($view::TEMPLATE_MODE_CP);
                $template = '_special/email';
            }

            try {
                $message->setHtmlBody($view->renderTemplate($template, array_merge($variables, [
                    'body' => Template::raw(Markdown::process($textBody)),
                ])));
            } catch (\Throwable $e) {
                // Just log it and don't worry about the HTML body
                Craft::warning('Error rendering email template: ' . $e->getMessage(), __METHOD__);
                Craft::$app->getErrorHandler()->logException($e);
            }

            // Set things back to normal
            Craft::$app->language = $language;
            $view->setTemplateMode($templateMode);
        }

        // Set the default sender if there isn't one already
        if (!$message->getFrom()) {
            $message->setFrom($this->from);
        }

        // Apply the testToEmailAddress config setting
        $testToEmailAddress = Craft::$app->getConfig()->getGeneral()->testToEmailAddress;
        if (!empty($testToEmailAddress)) {
            $to = [];
            foreach ((array)$testToEmailAddress as $emailAddress => $name) {
                if (is_numeric($emailAddress)) {
                    $to[$name] = Craft::t('app', 'Test Recipient');
                } else {
                    $to[$emailAddress] = $name;
                }
            }
            $message->setTo($to);
            $message->setCc(null);
            $message->setBcc(null);
        }

        try {
            return parent::send($message);
        } catch (\Throwable $e) {
            $eMessage = $e->getMessage();

            // Remove the stack trace to get rid of any sensitive info. Note that Swiftmailer includes a debug
            // backlog in the exception message. :-/
            $eMessage = substr($eMessage, 0, strpos($eMessage, 'Stack trace:') - 1);
            Craft::warning('Error sending email: ' . $eMessage);

            $this->afterSend($message, false);
            return false;
        }
    }
}
