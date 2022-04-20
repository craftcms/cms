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
    private array $_messages = [];

    /**
     * @var Module
     */
    public $module;

    public function init(): void
    {
        if (!$this->mailPath) {
            $this->mailPath = "{$this->module->dataPath}/mail";
        }

        if (!$this->module->fs) {
            return;
        }

        Event::on(BaseMailer::class, BaseMailer::EVENT_AFTER_SEND, function($event) {
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
            $this->module->fs->write("$this->mailPath/$fileName", $message->toString());
            $messageData['file'] = $fileName;

            $this->_messages[] = $messageData;
        });
    }

    /**
     * @param mixed $attr
     * @return string
     */
    private function convertParams(mixed $attr): string
    {
        if (is_array($attr)) {
            $attr = implode(', ', array_keys($attr));
        }

        return $attr;
    }

    /**
     * @inheritdoc
     */
    public function save(): array
    {
        return $this->_messages;
    }

    /**
     * @inheritdoc
     */
    public function getMessagesFileName(): array
    {
        $names = [];
        foreach ($this->_messages as $message) {
            $names[] = $message['file'];
        }

        return $names;
    }
}
