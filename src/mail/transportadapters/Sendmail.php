<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;

/**
 * Sendmail implements a Sendmail transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Sendmail extends BaseTransportAdapter
{
    /**
     * @since 3.4.0
     */
    const DEFAULT_COMMAND = '/usr/sbin/sendmail -bs';

    /**
     * @var string|null The command to pass to the transport
     * @since 3.4.0
     */
    public $command;

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'command',
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function attributeLabels()
    {
        return [
            'command' => Craft::t('app', 'Sendmail Command'),
        ];
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['command'], 'trim'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Sendmail';
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/mailertransportadapters/Sendmail/settings', [
            'adapter' => $this,
            'defaultCommand' => self::DEFAULT_COMMAND,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function defineTransport()
    {
        return [
            'class' => \Swift_SendmailTransport::class,
            'command' => $this->command ? Craft::parseEnv($this->command) : self::DEFAULT_COMMAND,
        ];
    }
}
