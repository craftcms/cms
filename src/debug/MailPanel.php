<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use yii\base\Event;
use yii\mail\BaseMailer;
use yii\mail\MailEvent;
use yii\mail\MessageInterface;

/**
 * Debugger panel that collects and displays the generated emails.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class MailPanel extends \yii\debug\panels\MailPanel
{
    public $mailPath = null;
    public function init(): void
    {
        $this->mailPath = $this->mailPath ?? "{$this->module->dataPath}/mail";
        parent::init();

        if (!$this->module->fs) {
            return;
        }

        Event::on(BaseMailer::class, BaseMailer::EVENT_AFTER_SEND, function ($event) {
            /* @var $event MailEvent */
            $message = $event->message;
            /* @var $message MessageInterface */
            $messageData = [
                'isSuccessful' => $event->isSuccessful,
                'from' => $this->convertParams($message->getFrom()),
                'to' => $this->convertParams($message->getTo()),
                'reply' => $this->convertParams($message->getReplyTo()),
                'cc' => $this->convertParams($message->getCc()),
                'bcc' => $this->convertParams($message->getBcc()),
                'subject' => $message->getSubject(),
                'charset' => $message->getCharset(),
            ];

            // store message as file
            $fileName = $event->sender->generateMessageFileName();
            $this->module->fs->write("$mailPath/$fileName", $message->toString())
            $messageData['file'] = $fileName;

            $this->_messages[] = $messageData;
        });

    }
}
