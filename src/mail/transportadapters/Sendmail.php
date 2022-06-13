<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use craft\helpers\StringHelper;

/**
 * Sendmail implements a Sendmail transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @mixin EnvAttributeParserBehavior
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
        $rules[] = [
            ['command'],
            'in',
            'range' => function() {
                return $this->_allowedCommands();
            },
            'when' => function() {
                return $this->getUnparsedAttribute('command') === null;
            },
        ];
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
        $commandOptions = array_map(function(string $command) {
            return [
                'label' => $command,
                'value' => $command,
                'data' => [
                    'data' => [
                        'hint' => null,
                    ],
                ],
            ];
        }, $this->_allowedCommands());

        return Craft::$app->getView()->renderTemplate('_components/mailertransportadapters/Sendmail/settings', [
            'adapter' => $this,
            'commandOptions' => $commandOptions,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function defineTransport()
    {
        return [
            'class' => \Swift_SendmailTransport::class,
            'command' => $this->command ? App::parseEnv($this->command) : self::DEFAULT_COMMAND,
        ];
    }

    /**
     * Returns the allowed command values.
     *
     * @return string[]
     */
    private function _allowedCommands(): array
    {
        // Grab the current value from the project config rather than $this->command, so we don't risk
        // polluting the allowed commands with a tampered value that came from the post data
        $command = Craft::$app->getProjectConfig()->get('email.transportSettings.command');

        return array_unique(array_filter([
            (!StringHelper::startsWith($command, '$') || StringHelper::startsWith('$HTTP_')) ? $command : null,
            self::DEFAULT_COMMAND,
            ini_get('sendmail_path'),
        ]));
    }
}
