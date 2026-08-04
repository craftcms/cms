<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\TransportAdapterInterface;
use craft\validators\TemplateValidator;
use CraftCms\Cms\Support\Facades\Sites;
use yii\validators\EmailValidator;
use function CraftCms\Cms\t;

/**
 * MailSettings Model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MailSettings extends Model
{
    /**
     * @var string|null The default email address that emails should be sent from
     */
    public ?string $fromEmail = null;

    /**
     * @var string|null The default Reply-To email address that emails should have
     * @since 3.4.0
     */
    public ?string $replyToEmail = null;

    /**
     * @var string|null The default name that emails should be sent from
     */
    public ?string $fromName = null;

    /**
     * @var string|null The template that emails should be sent with
     * @deprecated 6.0.0 use a Laravel mailable view instead.
     */
    public ?string $template = null;

    /**
     * @var array Site-specific overrides
     * @since 5.6.0
     * @deprecated 6.0.0 Use the email settings in Settings → Email instead.
     */
    public array $siteOverrides = [];

    /**
     * @var class-string<TransportAdapterInterface>|null The transport type that should be used
     */
    public ?string $transportType = Sendmail::class;

    /**
     * @var array|null The transport type’s settings
     */
    public ?array $transportSettings = null;

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'fromEmail',
                    'replyToEmail',
                    'fromName',
                    'template',
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
            'fromEmail' => t('System Email Address'),
            'replyToEmail' => t('Reply-To Address'),
            'fromName' => t('Sender Name'),
            'template' => t('HTML Email Template'),
            'transportType' => t('Transport Type'),
            'siteOverrides' => t('Site Overrides'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['fromEmail', 'fromName', 'transportType'], 'required'];
        $rules[] = [['fromEmail', 'replyToEmail'], 'email'];
        $rules[] = [['template'], TemplateValidator::class];

        $rules[] = [['siteOverrides'], function() {
            foreach ($this->siteOverrides as $siteUid => $overrides) {
                foreach (['fromEmail', 'replyToEmail'] as $key) {
                    if (isset($overrides[$key]) && !str_starts_with($overrides[$key], '$')) {
                        $validator = new EmailValidator([
                            'message' => t('{attribute} is not a valid email address.', [
                                'attribute' => sprintf(
                                    '%s - %s',
                                    Sites::getSiteByUid($siteUid)->getUiLabel(),
                                    $this->attributeLabels()[$key],
                                ),
                            ]),
                        ]);
                        if (!$validator->validate($overrides[$key], $error)) {
                            $this->addError('siteOverrides', $error);
                        }
                    }
                }
            }
        }];

        return $rules;
    }

    /**
     * Sets the site overrides.
     *
     * @param array $siteOverrides
     * @since 5.6.0
     * @deprecated 6.0.0 Use the email settings in Settings → Email instead.
     */
    public function setSiteOverrides(array $siteOverrides): void
    {
        $this->siteOverrides = array_filter(array_map(fn(array $overrides) => array_filter($overrides), $siteOverrides));
    }
}
