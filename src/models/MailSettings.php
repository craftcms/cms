<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\TransportAdapterInterface;
use craft\validators\TemplateValidator;
use yii\validators\EmailValidator;

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
     */
    public ?string $template = null;

    /**
     * @var array Site-specific overrides
     * @since 5.6.0
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
            'fromEmail' => Craft::t('app', 'System Email Address'),
            'replyToEmail' => Craft::t('app', 'Reply-To Address'),
            'fromName' => Craft::t('app', 'Sender Name'),
            'template' => Craft::t('app', 'HTML Email Template'),
            'transportType' => Craft::t('app', 'Transport Type'),
            'siteOverrides' => Craft::t('app', 'Site Overrides'),
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
            $sitesService = Craft::$app->getSites();
            foreach ($this->siteOverrides as $siteUid => $overrides) {
                foreach (['fromEmail', 'replyToEmail'] as $key) {
                    if (isset($overrides[$key]) && !str_starts_with($overrides[$key], '$')) {
                        $validator = new EmailValidator([
                            'message' => Craft::t('yii', '{attribute} is not a valid email address.', [
                                'attribute' => sprintf(
                                    '%s - %s',
                                    $sitesService->getSiteByUid($siteUid)->getUiLabel(),
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
     */
    public function setSiteOverrides(array $siteOverrides): void
    {
        $this->siteOverrides = array_filter(array_map(fn(array $overrides) => array_filter($overrides), $siteOverrides));
    }
}
