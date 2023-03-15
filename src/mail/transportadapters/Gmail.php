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
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * Smtp implements a Gmail transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Gmail extends BaseTransportAdapter
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Gmail';
    }

    /**
     * @var string|null The username that should be used
     */
    public ?string $username = null;

    /**
     * @var string|null The password that should be used
     */
    public ?string $password = null;

    /**
     * @var int The timeout duration (in seconds)
     * @deprecated in 4.3.7.
     */
    public int $timeout = 10;

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'username',
                    'password',
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'username' => Craft::t('app', 'Username'),
            'password' => Craft::t('app', 'Password'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['username', 'password'], 'trim'];
        $rules[] = [['username', 'password'], 'required'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_components/mailertransportadapters/Gmail/settings.twig', [
            'adapter' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function defineTransport(): array|AbstractTransport
    {
        return [
            'scheme' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 0,
            'username' => App::parseEnv($this->username),
            'password' => App::parseEnv($this->password),
        ];
    }
}
