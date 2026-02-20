<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use CraftCms\Cms\Support\Env;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

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
            'username' => t('Username'),
            'password' => t('Password'),
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
        return $this->settingsHtml(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        return template('_components/mailertransportadapters/Gmail/settings', [
            'adapter' => $this,
            'readOnly' => $readOnly,
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
            'username' => Env::parse($this->username),
            'password' => Env::parse($this->password),
        ];
    }
}
