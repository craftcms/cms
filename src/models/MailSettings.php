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
use craft\validators\TemplateValidator;

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
    public $fromEmail;

    /**
     * @var string|null The default Reply-To email address that emails should have
     * @since 3.4.0
     */
    public $replyToEmail;

    /**
     * @var string|null The default name that emails should be sent from
     */
    public $fromName;

    /**
     * @var string|null The template that emails should be sent with
     */
    public $template;

    /**
     * @var string|null The transport type that should be used
     */
    public $transportType = Sendmail::class;

    /**
     * @var array|null The transport type’s settings
     */
    public $transportSettings;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'fromEmail',
                'replyToEmail',
                'fromName',
                'template',
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fromEmail' => Craft::t('app', 'System Email Address'),
            'replyToEmail' => Craft::t('app', 'Reply-To Address'),
            'fromName' => Craft::t('app', 'Sender Name'),
            'template' => Craft::t('app', 'HTML Email Template'),
            'transportType' => Craft::t('app', 'Transport Type'),
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

        return $rules;
    }
}
