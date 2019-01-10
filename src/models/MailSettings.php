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
use craft\validators\TemplateValidator;

/**
 * MailSettings Model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MailSettings extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The default email address that emails should be sent from
     */
    public $fromEmail;

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
    public $transportType;

    /**
     * @var array|null The transport type’s settings
     */
    public $transportSettings;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'fromEmail',
                    'fromName',
                    'template',
                ],
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fromEmail' => Craft::t('app', 'System Email Address'),
            'fromName' => Craft::t('app', 'Sender Name'),
            'template' => Craft::t('app', 'HTML Email Template'),
            'transportType' => Craft::t('app', 'Transport Type'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['fromEmail', 'fromName', 'transportType'], 'required'];
        $rules[] = [['fromEmail'], 'email'];
        $rules[] = [['template'], TemplateValidator::class];

        return $rules;
    }
}
