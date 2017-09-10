<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\mail;

use Craft;
use craft\elements\User;
use craft\helpers\Template;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;
use yii\mail\MessageInterface;

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
     * @var string|null The email template that should be used
     */
    public $template;

    /**
     * @var string|array|User|User[]|null $from The default sender’s email address, or their user model(s).
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
     * This method will log a message about the email being sent.
     * If [[useFileTransport]] is true, it will save the email as a file under [[fileTransportPath]].
     * Otherwise, it will call [[sendMessage()]] to send the email to its recipient(s).
     * Child classes should implement [[sendMessage()]] with the actual email sending logic.
     *
     * @param MessageInterface $message The email message instance to be sent.
     *
     * @return bool Whether the message has been sent successfully.
     *
     */
    public function send($message)
    {
        if ($message instanceof Message && $message->key !== null) {
            $systemMessage = Craft::$app->getSystemMessages()->getMessage($message->key, $message->language);
            $subjectTemplate = $systemMessage->subject;
            $textBodyTemplate = $systemMessage->body;

            $view = Craft::$app->getView();
            $templateMode = $view->getTemplateMode();
            $language = Craft::$app->language;

            $variables = $message->variables ?: [];
            $variables['emailKey'] = $message->key;

            if ($message->language !== null) {
                Craft::$app->language = $message->language;
            }

            // Don't let Twig use the HTML escaping strategy on the subject or plain text portion body of the email.
            /** @var \Twig_Extension_Escaper $ext */
            $ext = $view->getTwig()->getExtension(\Twig_Extension_Escaper::class);
            $ext->setDefaultStrategy(false);
            $subject = $view->renderString($subjectTemplate, $variables);
            $textBody = $view->renderString($textBodyTemplate, $variables);
            $ext->setDefaultStrategy('html');

            // Is there a custom HTML template set?
            if (Craft::$app->getEdition() >= Craft::Client && $this->template) {
                $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
                $template = $this->template;
            } else {
                // Default to the _special/email.html template
                $view->setTemplateMode($view::TEMPLATE_MODE_CP);
                $template = '_special/email';
            }

            $htmlBody = $view->renderTemplate($template, array_merge($variables, [
                'body' => Template::raw(Markdown::process($textBody)),
            ]));

            $message
                ->setSubject($subject)
                ->setHtmlBody($htmlBody)
                ->setTextBody($textBody);

            // Set things back to normal
            Craft::$app->language = $language;
            $view->setTemplateMode($templateMode);
        }

        // Set the default sender if there isn't one already
        if (!$message->getFrom()) {
            $message->setFrom($this->from);
        }

        // Apply the testToEmailAddress config setting
        $testToEmailAddress = (array)Craft::$app->getConfig()->getGeneral()->testToEmailAddress;
        if (!empty($testToEmailAddress)) {
            $to = [];
            foreach ($testToEmailAddress as $emailAddress => $name) {
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

        return parent::send($message);
    }
}
